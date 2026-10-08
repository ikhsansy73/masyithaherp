<?php

namespace App\Services\Billing;

use App\Enums\DiscountType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\JournalSource;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\Discount;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use App\Services\Shared\DocumentSequenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The invoice write path (doc 04). Shared by the monthly batch
 * (InvoiceBatchService), one-off tahunan/insidental batches, and manual
 * per-student invoices: builds invoice + items from a student_fee,
 * posts the issue journal (rules #1 + #2), and voids issued invoices
 * with a mirror JE.
 */
class InvoiceService
{
    public function __construct(
        private readonly DocumentSequenceService $sequences,
        private readonly JournalPostingService $journals,
    ) {}

    /**
     * Create a draft invoice from one student_fee: a posisi item with the
     * fee amount plus one potongan item per eligible discount
     * (posting rule #2). Caller wraps in a transaction when batching.
     */
    public function buildForStudentFee(
        StudentFee $fee,
        ?int $periodMonth,
        Carbon $invoiceDate,
        Carbon $dueDate,
        ?int $batchId = null,
        string $source = 'batch',
        ?User $actor = null,
    ): Invoice {
        $feeType = $fee->feeType;
        $year = $fee->academicYear;

        if ($feeType === null || $year === null) {
            throw new AccountingException('Penetapan biaya tidak valid (jenis biaya atau tahun ajaran hilang).');
        }

        $periodLabel = $periodMonth !== null
            ? static::monthLabel($year, $periodMonth)
            : $year->name;

        $fundId = $this->resolveFundId($feeType, $year, $fee->student?->enrollmentForYear($year->getKey())?->grade_level);

        $invoice = Invoice::query()->create([
            'number' => $this->nextNumber($year),
            'student_id' => $fee->student_id,
            'academic_year_id' => $year->getKey(),
            'invoice_batch_id' => $batchId,
            'invoice_date' => $invoiceDate->copy(),
            'due_date' => $dueDate->copy(),
            'period_month' => $periodMonth,
            'description' => trim($feeType->name.' — '.$periodLabel),
            'status' => InvoiceStatus::Draft,
            'total' => 0,
            'paid_amount' => 0,
            'fund_id' => $fundId,
            'source' => $source,
        ]);

        $invoice->items()->create([
            'item_type' => InvoiceItemType::Posisi,
            'fee_type_id' => $feeType->getKey(),
            'description' => $periodMonth !== null
                ? trim($feeType->name.' '.$periodLabel)
                : $feeType->name,
            'amount' => $fee->amount,
            'revenue_account_id' => $feeType->revenue_account_id,
            'fund_id' => $fundId,
        ]);

        $discountTotal = 0;

        foreach ($this->eligibleDiscounts($fee, $periodMonth) as $discount) {
            $amount = static::discountAmount($discount, $fee->amount);
            $discountTotal += $amount;

            $invoice->items()->create([
                'item_type' => InvoiceItemType::Potongan,
                'fee_type_id' => null,
                'discount_id' => $discount->getKey(),
                'description' => $discount->name,
                'amount' => $amount,
                'revenue_account_id' => $this->discountExpenseAccountId(),
                'fund_id' => $fundId,
            ]);
        }

        $invoice->update(['total' => $fee->amount - $discountTotal]);

        return $invoice;
    }

    /**
     * Active discounts for a student fee, matching the year and fee type
     * (null fee_type_id = all types). The month window is only applied
     * when a period month exists (SPP); tahunan/insidental invoices apply
     * the discount regardless of the window.
     *
     * @return Collection<int, Discount>
     */
    public function eligibleDiscounts(StudentFee $fee, ?int $periodMonth): Collection
    {
        return Discount::query()
            ->where('student_id', $fee->student_id)
            ->where('academic_year_id', $fee->academic_year_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->whereNull('fee_type_id')
                ->orWhere('fee_type_id', $fee->fee_type_id))
            ->when($periodMonth !== null, fn ($query) => $query
                ->where('start_month', '<=', $periodMonth)
                ->where('end_month', '>=', $periodMonth))
            ->orderBy('id')
            ->get();
    }

    /**
     * Compute one discount's rupiah value against a base amount.
     */
    public static function discountAmount(Discount $discount, int $base): int
    {
        $amount = match ($discount->type) {
            DiscountType::Percent => (int) round($base * $discount->value / 100),
            DiscountType::Fixed => $discount->value,
        };

        return min($amount, $base);
    }

    /**
     * The fee fund for JE dimensions: the structure's fund (grade-matched
     * beats the flat row, doc 02) or the revenue account's default fund.
     */
    public function resolveFundId(FeeType $feeType, AcademicYear $year, ?int $gradeLevel): ?int
    {
        $structureFundId = FeeStructure::query()
            ->where('academic_year_id', $year->getKey())
            ->where('fee_type_id', $feeType->getKey())
            ->where(fn ($query) => $query
                ->where('grade_level', $gradeLevel)
                ->orWhereNull('grade_level'))
            ->orderByDesc('grade_level')
            ->value('fund_id');

        if ($structureFundId !== null) {
            return (int) $structureFundId;
        }

        return $feeType->revenueAccount?->default_fund_id;
    }

    /**
     * Issue drafts: validate they are all still draft, post one journal
     * for the set (rules #1 + #2), and flip them to issued.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @param  \Illuminate\Database\Eloquent\Model|null  $reference
     */
    public function issueInvoices(Collection $invoices, string $description, $reference, User $actor): JournalEntry
    {
        return DB::transaction(function () use ($invoices, $description, $reference, $actor): JournalEntry {
            $locked = Invoice::query()
                ->whereKey($invoices->modelKeys())
                ->lockForUpdate()
                ->get();

            foreach ($locked as $invoice) {
                if ($invoice->status !== InvoiceStatus::Draft) {
                    throw new AccountingException("Tagihan {$invoice->number} tidak berstatus draft dan tidak dapat diterbitkan.");
                }
            }

            $entry = $this->journals->post($this->buildIssueDraft($locked, $description, $reference, $actor));

            Invoice::query()
                ->whereKey($locked->modelKeys())
                ->update(['status' => InvoiceStatus::Issued->value]);

            return $entry;
        });
    }

    /**
     * Void one issued invoice with a mirror JE (doc 04 §3). An invoice
     * that already received payment must have its kwitansi voided first.
     */
    public function voidInvoice(Invoice $invoice, string $reason, User $actor): void
    {
        DB::transaction(function () use ($invoice, $reason, $actor): void {
            $invoice = Invoice::query()
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invoice->status->isPayable()) {
                throw new AccountingException('Hanya tagihan berstatus terbit yang dapat dibatalkan.');
            }

            if ($invoice->paid_amount > 0) {
                throw new AccountingException('Tagihan sudah menerima pembayaran. Batalkan kwitansi terlebih dahulu.');
            }

            $invoice->load('items');

            $mirror = $this->mirror($this->buildIssueDraft(
                collect([$invoice]),
                "Pembatalan {$invoice->number}: {$reason}",
                $invoice,
                $actor,
            ));

            $this->journals->post($mirror);

            $invoice->forceFill([
                'status' => InvoiceStatus::Void,
                'voided_reason' => $reason,
            ])->save();
        });
    }

    /**
     * The issue journal for a set of invoices (posting rules #1 + #2):
     * debit 1-1300 gross; credit each posisi's revenue account; debit
     * 5-1500 and credit 1-1300 per potongan. Lines grouped by
     * (account, fund).
     *
     * @param  Collection<int, Invoice>  $invoices
     * @param  \Illuminate\Database\Eloquent\Model|null  $reference
     */
    public function buildIssueDraft(Collection $invoices, string $description, $reference, User $actor): JournalDraft
    {
        $receivableAccountId = static::receivableAccountId();
        $discountExpenseAccountId = $this->discountExpenseAccountId();

        // [fundId => amount]
        $receivableDebits = [];
        $receivableDiscountCredits = [];
        // "accountId:fundId" => amount
        $revenueCredits = [];
        $discountExpenseDebit = 0;

        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                if ($item->item_type === InvoiceItemType::Posisi) {
                    $receivableDebits[$invoice->fund_id] = ($receivableDebits[$invoice->fund_id] ?? 0) + $item->amount;
                    $key = $item->revenue_account_id.':'.($item->fund_id ?? '');
                    $revenueCredits[$key] = ($revenueCredits[$key] ?? 0) + $item->amount;
                } else {
                    $discountExpenseDebit += $item->amount;
                    $receivableDiscountCredits[$invoice->fund_id] = ($receivableDiscountCredits[$invoice->fund_id] ?? 0) + $item->amount;
                }
            }
        }

        $lines = [];

        foreach ($receivableDebits as $fundId => $amount) {
            $lines[] = JournalDraftLine::debit($receivableAccountId, $amount, $fundId);
        }

        foreach ($revenueCredits as $key => $amount) {
            [$accountId, $fundId] = explode(':', $key);
            $lines[] = JournalDraftLine::credit((int) $accountId, $amount, $fundId === '' ? null : (int) $fundId);
        }

        if ($discountExpenseDebit > 0) {
            $lines[] = JournalDraftLine::debit($discountExpenseAccountId, $discountExpenseDebit);

            foreach ($receivableDiscountCredits as $fundId => $amount) {
                $lines[] = JournalDraftLine::credit($receivableAccountId, $amount, $fundId);
            }
        }

        return new JournalDraft(
            entryDate: Carbon::today(),
            description: $description,
            userId: $actor->getKey(),
            source: JournalSource::Otomatis,
            reference: $reference,
            lines: $lines,
        );
    }

    /**
     * The invoice number series "INV/2026-2027/000123", one sequence per
     * academic year.
     */
    public function nextNumber(AcademicYear $year): string
    {
        $slug = $year->starts_at->format('Y').'-'.$year->ends_at->format('Y');

        return $this->sequences->next(
            key: 'invoice',
            period: $slug,
            prefix: "INV/{$slug}/",
        );
    }

    /**
     * Calendar label of a batch month inside the academic year,
     * e.g. month 1 of 2026/2027 → "Januari 2027".
     */
    public static function monthLabel(AcademicYear $year, int $calendarMonth): string
    {
        $calendarYear = $calendarMonth >= $year->starts_at->month
            ? $year->starts_at->year
            : $year->starts_at->year + 1;

        return Carbon::create($calendarYear, $calendarMonth, 1)
            ->locale(config('app.locale'))
            ->translatedFormat('F Y');
    }

    /**
     * The debit/credit-swapped draft, used for invoice voids.
     */
    public static function mirror(JournalDraft $draft): JournalDraft
    {
        return new JournalDraft(
            entryDate: $draft->entryDate,
            description: $draft->description,
            userId: $draft->userId,
            source: $draft->source,
            reference: $draft->reference,
            lines: collect($draft->lines)
                ->map(fn (JournalDraftLine $line): JournalDraftLine => new JournalDraftLine(
                    accountId: $line->accountId,
                    debit: $line->credit,
                    credit: $line->debit,
                    fundId: $line->fundId,
                    memo: $line->memo,
                ))
                ->all(),
        );
    }

    /**
     * 1-1300 Piutang Siswa — the single receivable control account.
     */
    public static function receivableAccountId(): int
    {
        $id = Account::query()->where('code', '1-1300')->value('id');

        if ($id === null) {
            throw new AccountingException('Akun Piutang Siswa (1-1300) tidak ditemukan. Jalankan seeder COA.');
        }

        return (int) $id;
    }

    /**
     * 5-1500 Beban Beasiswa & Potongan — booked on every discount
     * (posting rule #2).
     */
    private function discountExpenseAccountId(): int
    {
        $id = Account::query()->where('code', '5-1500')->value('id');

        if ($id === null) {
            throw new AccountingException('Akun Beban Beasiswa & Potongan (5-1500) tidak ditemukan. Jalankan seeder COA.');
        }

        return (int) $id;
    }
}
