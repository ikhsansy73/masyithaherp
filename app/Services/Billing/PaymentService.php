<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\JournalSource;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use App\Services\Shared\DocumentSequenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly DocumentSequenceService $sequences,
        private readonly JournalPostingService $journals,
    ) {}

    /**
     * Record a payment with its allocations (doc 04 §4). Partial
     * payments allowed; per-invoice over-allocation blocked; a remainder
     * stays unallocated (credited to 2-1500) and surfaces in the
     * "Pembayaran belum dialokasikan" widget.
     */
    public function record(PaymentSource $source): Payment
    {
        if ($source->amount <= 0) {
            throw new AccountingException('Jumlah pembayaran harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($source): Payment {
            $cashAccount = CashAccount::query()
                ->whereKey($source->cashAccountId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($cashAccount === null) {
                throw new AccountingException('Kas/bank tidak aktif atau tidak ditemukan.');
            }

            $student = Student::query()->findOrFail($source->studentId);

            $allocations = $source->allocations === []
                ? static::proposeFifoAllocations($source->studentId, $source->amount)
                : $source->allocations;

            $lockedInvoices = Invoice::query()
                ->whereKey(array_keys($allocations))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            return $this->recordWithinTransaction($source, $cashAccount, $student, $allocations, $lockedInvoices);
        });
    }

    private function recordWithinTransaction(
        PaymentSource $source,
        CashAccount $cashAccount,
        Student $student,
        array $allocations,
        Collection $lockedInvoices,
    ): Payment {
        $userId = $source->userId ?? auth()->id();

        if ($userId === null) {
            throw new AccountingException('Penerimaan harus dicatat oleh pengguna terautentikasi.');
        }

        $totalAllocated = 0;
        $allocatedPerFund = [];

        foreach ($allocations as $invoiceId => $amount) {
            $invoice = $lockedInvoices->get($invoiceId);

            if ($invoice === null || (int) $invoice->student_id !== $source->studentId) {
                throw new AccountingException('Tagihan tidak ditemukan untuk siswa ini.');
            }

            if (! $invoice->status->isPayable()) {
                throw new AccountingException("Tagihan {$invoice->number} berstatus {$invoice->status->label()} tidak dapat dibayar.");
            }

            if ($amount > $invoice->remainingAmount()) {
                throw new AccountingException('Pembayaran melebihi sisa tagihan.');
            }

            if ($amount <= 0) {
                throw new AccountingException('Alokasi pembayaran harus lebih besar dari nol.');
            }

            $totalAllocated += $amount;
            $allocatedPerFund[$invoice->fund_id ?? 0] = ($allocatedPerFund[$invoice->fund_id ?? 0] ?? 0) + $amount;
        }

        if ($totalAllocated > $source->amount) {
            throw new AccountingException('Total alokasi melebihi jumlah pembayaran.');
        }

        $number = $this->sequences->next(
            key: 'kwitansi',
            period: (string) $source->paymentDate->year,
            prefix: 'KW/'.$source->paymentDate->year.'/',
        );

        $payment = Payment::query()->create([
            'number' => $number,
            'payment_date' => $source->paymentDate->copy(),
            'method' => $source->method,
            'cash_account_id' => $cashAccount->getKey(),
            'student_id' => $source->studentId,
            'amount' => $source->amount,
            'reference' => $source->reference,
            'received_by' => $userId,
            'notes' => $source->notes,
        ]);

        $payment->allocations()->createMany(
            collect($allocations)
                ->map(fn (int $amount, int $invoiceId): array => [
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'allocated_by' => $userId,
                ])->values()->all()
        );

        $entry = $this->journals->post($this->buildPaymentDraft(
            cashAccountId: $cashAccount->account_id,
            amount: $source->amount,
            allocatedPerFund: $allocatedPerFund,
            description: "Penerimaan {$number} — {$student->full_name}",
            reference: $payment,
            userId: (int) $userId,
        ));

        $payment->forceFill(['journal_entry_id' => $entry->getKey()])->save();

        $this->recomputeInvoices($lockedInvoices);

        return $payment;
    }

    /**
     * Void a kwitansi: mirror JE (rule #4), a reversal payment row
     * (negative amount, fresh kwitansi number — the original stays
     * consumed), allocations removed, invoice statuses recomputed.
     */
    public function void(Payment $payment, string $reason, User $actor): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $actor): Payment {
            $payment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->reversed_by_payment_id !== null) {
                throw new AccountingException('Kwitansi sudah dibatalkan.');
            }

            if ($payment->journalEntry === null) {
                throw new AccountingException('Kwitansi tidak memiliki jurnal.');
            }

            $reversalEntry = $this->journals->void(
                $payment->journalEntry,
                $reason,
                "Pembatalan kwitansi {$payment->number}: {$reason}",
            );

            $reversal = Payment::query()->create([
                'number' => $this->sequences->next(
                    key: 'kwitansi',
                    period: (string) $payment->payment_date->year,
                    prefix: 'KW/'.$payment->payment_date->year.'/',
                ),
                'student_id' => $payment->student_id,
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
                'cash_account_id' => $payment->cash_account_id,
                'amount' => -$payment->amount,
                'reference' => null,
                'received_by' => $actor->getKey(),
                'notes' => "Pembatalan {$payment->number}: {$reason}",
            ]);

            $reversal->forceFill(['journal_entry_id' => $reversalEntry->getKey()])->save();
            $payment->forceFill(['reversed_by_payment_id' => $reversal->getKey()])->save();

            $invoiceIds = $payment->allocations()->pluck('invoice_id');

            $payment->allocations()->delete();

            $this->recomputeInvoices(
                Invoice::query()->whereKey($invoiceIds)->get()->keyBy('id'),
            );

            return $reversal;
        });
    }

    /**
     * FIFO pre-fill (doc 04 §4): outstanding invoices oldest due first,
     * each filled to its remaining amount until the payment runs out.
     *
     * @return array<int, int> invoice_id => amount
     */
    public static function proposeFifoAllocations(int $studentId, int $amount): array
    {
        $oldestFirst = Invoice::query()
            ->where('student_id', $studentId)
            ->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid])
            ->whereColumn('paid_amount', '<', 'total')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $left = $amount;
        $plan = [];

        foreach ($oldestFirst as $invoice) {
            if ($left <= 0) {
                break;
            }

            $remaining = $invoice->remainingAmount();
            $take = min($remaining, $left);
            $plan[$invoice->getKey()] = $take;
            $left -= $take;
        }

        return $plan;
    }

    /**
     * Recompute the derived paid_amount cache and status — written only
     * by the payment service (doc 04 §3).
     *
     * @param  Collection<int, Invoice>  $invoices
     */
    private function recomputeInvoices(Collection $invoices): void
    {
        foreach ($invoices as $invoice) {
            $paid = (int) $invoice->allocations()->sum('amount');

            $invoice->paid_amount = $paid;
            $invoice->status = match (true) {
                $paid === 0 => InvoiceStatus::Issued,
                $paid >= $invoice->total => InvoiceStatus::Paid,
                default => InvoiceStatus::PartiallyPaid,
            };
            $invoice->save();
        }
    }

    /**
     * JE #3 + the 2-1500 remainder credit. Fund follows the invoice's
     * fee fund (doc 03 rule #3); the cash debit takes the cash
     * account's default fund.
     */
    private function buildPaymentDraft(
        int $cashAccountId,
        int $amount,
        array $allocatedPerFund,
        string $description,
        Payment $reference,
        ?int $userId,
    ): JournalDraft {
        $lines = [
            JournalDraftLine::debit($cashAccountId, $amount),
        ];

        foreach ($allocatedPerFund as $fundId => $allocated) {
            $lines[] = JournalDraftLine::credit(
                static::receivableAccountId(),
                $allocated,
                $fundId === 0 ? null : (int) $fundId,
            );
        }

        $remainder = $amount - array_sum($allocatedPerFund);

        if ($remainder > 0) {
            $lines[] = JournalDraftLine::credit(
                (int) Account::query()->where('code', '2-1500')->value('id'),
                $remainder,
                null,
                'Penerimaan belum dialokasikan',
            );
        }

        return new JournalDraft(
            entryDate: Carbon::today(),
            description: $description,
            userId: (int) $userId,
            source: JournalSource::Otomatis,
            reference: $reference,
            lines: $lines,
        );
    }

    /**
     * 1-1300 — the same receivable control account as the issue JE.
     */
    private static function receivableAccountId(): int
    {
        return InvoiceService::receivableAccountId();
    }
}
