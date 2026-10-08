<?php

namespace App\Services\Billing;

use App\Enums\FeeCategory;
use App\Enums\InvoiceBatchStatus;
use App\Enums\InvoiceStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\InvoiceBatch;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Batch invoice generation and issuance (doc 04 §2). Drafts are freely
 * editable/deletable and regenerate idempotently; Terbitkan posts one
 * journal entry per batch (rules #1 + #2); issued batches are only
 * voidable (mirror JE).
 */
class InvoiceBatchService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly JournalPostingService $journals,
    ) {}

    /**
     * Generate (or top up) the draft batch for one fee type + month.
     * Idempotent: the unique (academic_year, fee_type, period_month)
     * constraint means reruns reuse the draft batch and only add invoices
     * for students who do not hold one yet. Returns null when nothing is
     * eligible and no draft exists.
     */
    public function generate(
        AcademicYear $year,
        FeeType $feeType,
        ?int $periodMonth,
        ?int $gradeFilter = null,
        ?User $actor = null,
    ): ?InvoiceBatch {
        if (! $feeType->is_active) {
            throw new AccountingException('Jenis biaya tidak aktif.');
        }

        if ($feeType->category === FeeCategory::Bulanan) {
            if ($periodMonth === null || $periodMonth < 1 || $periodMonth > 12) {
                throw new AccountingException('Bulan tagihan (1-12) wajib diisi untuk jenis biaya bulanan.');
            }
        } elseif ($periodMonth !== null) {
            throw new AccountingException("Jenis biaya {$feeType->name} tidak bulanan; biarkan bulan tagihan kosong.");
        }

        return DB::transaction(function () use ($year, $feeType, $periodMonth, $gradeFilter, $actor): ?InvoiceBatch {
            $batch = InvoiceBatch::query()
                ->where('academic_year_id', $year->getKey())
                ->where('fee_type_id', $feeType->getKey())
                ->when($periodMonth !== null,
                    fn ($query) => $query->where('period_month', $periodMonth),
                    fn ($query) => $query->whereNull('period_month'))
                ->lockForUpdate()
                ->first();

            if ($batch !== null && $batch->status !== InvoiceBatchStatus::Draft) {
                $status = $batch->status->label();

                throw new AccountingException("Batch tagihan {$feeType->name} {$this->periodLabel($year, $periodMonth)} sudah {$status}.");
            }

            $fees = $this->eligibleStudentFees($year, $feeType, $periodMonth, $gradeFilter);

            if ($batch === null && $fees->isEmpty()) {
                return null;
            }

            $batch ??= InvoiceBatch::query()->create([
                'academic_year_id' => $year->getKey(),
                'fee_type_id' => $feeType->getKey(),
                'period_month' => $periodMonth,
                'grade_filter' => $gradeFilter,
                'status' => InvoiceBatchStatus::Draft,
                'generated_by' => $actor?->getKey(),
                'generated_at' => now(),
            ]);

            foreach ($fees as $fee) {
                if ($this->studentAlreadyInvoiced($fee, $periodMonth)) {
                    continue;
                }

                $this->invoices->buildForStudentFee(
                    fee: $fee,
                    periodMonth: $periodMonth,
                    invoiceDate: Carbon::today(),
                    dueDate: $this->dueDate($year, $periodMonth),
                    batchId: $batch->getKey(),
                );
            }

            $batch->refresh();
            $batch->update([
                'total_invoices' => $batch->invoices()->count(),
                'total_amount' => (int) $batch->invoices()->sum('total'),
            ]);

            return $batch;
        });
    }

    /**
     * Terbitkan (doc 04 §2): post one JE for the whole batch and flip
     * every draft invoice to issued.
     */
    public function issue(InvoiceBatch $batch, User $actor): InvoiceBatch
    {
        DB::transaction(function () use ($batch, $actor): void {
            $locked = InvoiceBatch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== InvoiceBatchStatus::Draft) {
                throw new AccountingException('Hanya batch draft yang dapat diterbitkan.');
            }

            $invoices = $locked->invoices()->where('status', InvoiceStatus::Draft)->get();

            if ($invoices->isEmpty()) {
                throw new AccountingException('Batch tidak memiliki tagihan untuk diterbitkan.');
            }

            $entry = $this->invoices->issueInvoices(
                $invoices,
                "Tagihan {$locked->feeType->name} {$this->periodLabel($locked->academicYear, $locked->period_month)}",
                $locked,
                $actor,
            );

            $locked->forceFill([
                'status' => InvoiceBatchStatus::Issued,
                'total_invoices' => $invoices->count(),
                'total_amount' => (int) $invoices->sum('total'),
                'generated_by' => $locked->generated_by ?? $actor->getKey(),
                'generated_at' => $locked->generated_at ?? now(),
                'journal_entry_id' => $entry->getKey(),
            ])->save();
        });

        return $batch->refresh();
    }

    /**
     * Void an issued batch: mirror the batch JE and void every invoice.
     * A batch that already received payments must be unwound payment by
     * payment first.
     */
    public function void(InvoiceBatch $batch, string $reason, User $actor): InvoiceBatch
    {
        DB::transaction(function () use ($batch, $reason): void {
            $locked = InvoiceBatch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== InvoiceBatchStatus::Issued) {
                throw new AccountingException('Hanya batch terbit yang dapat dibatalkan.');
            }

            $invoices = $locked->invoices()->lockForUpdate()->get();

            if ($invoices->contains(fn (Invoice $invoice): bool => $invoice->paid_amount > 0)) {
                throw new AccountingException('Batch memiliki tagihan yang sudah menerima pembayaran.');
            }

            foreach ($invoices as $invoice) {
                if ($invoice->status->isPayable()) {
                    $invoice->forceFill([
                        'status' => InvoiceStatus::Void,
                        'voided_reason' => $reason,
                    ])->save();
                }
            }

            if ($locked->journalEntry !== null) {
                $this->journals->void(
                    $locked->journalEntry,
                    $reason,
                    "Pembatalan batch {$locked->feeType->name} {$this->periodLabel($locked->academicYear, $locked->period_month)}",
                );
            }

            $locked->forceFill(['status' => InvoiceBatchStatus::Void])->save();
        });

        return $batch->refresh();
    }

    /**
     * Active student fees for the year + fee type, honouring the grade
     * filter (via the student's enrollment) and the first_month/months
     * window for monthly types.
     */
    private function eligibleStudentFees(AcademicYear $year, FeeType $feeType, ?int $periodMonth, ?int $gradeFilter)
    {
        return StudentFee::query()
            ->where('academic_year_id', $year->getKey())
            ->where('fee_type_id', $feeType->getKey())
            ->where('is_active', true)
            ->where('amount', '>', 0)
            ->with(['student.enrollments' => fn ($query) => $query->where('academic_year_id', $year->getKey())])
            ->get()
            ->filter(fn (StudentFee $fee): bool => $gradeFilter === null
                || $fee->student?->enrollments
                    ->contains(fn ($enrollment): bool => (int) $enrollment->grade_level === $gradeFilter))
            ->when($periodMonth !== null, fn ($fees) => $fees->filter(
                fn (StudentFee $fee): bool => $this->coversMonth($year, $fee, $periodMonth),
            ))
            ->values();
    }

    /**
     * Whether a monthly fee covers a calendar month, respecting
     * first_month and months across the academic-year wrap (an October
     * entrant with months = 9 covers Oct..Jun).
     */
    private function coversMonth(AcademicYear $year, StudentFee $fee, int $calendarMonth): bool
    {
        $firstIndex = static::academicMonthIndex($year, $fee->first_month ?? $year->starts_at->month);
        $currentIndex = static::academicMonthIndex($year, $calendarMonth);

        return $currentIndex >= $firstIndex
            && $currentIndex < $firstIndex + max(1, $fee->months);
    }

    /**
     * Academic month index: July (the year's start month) = 1.
     */
    public static function academicMonthIndex(AcademicYear $year, int $calendarMonth): int
    {
        return (($calendarMonth - $year->starts_at->month + 12) % 12) + 1;
    }

    /**
     * The generator skips students who already hold a non-void invoice
     * for this fee type + month (doc 04 §2 idempotency).
     */
    private function studentAlreadyInvoiced(StudentFee $fee, ?int $periodMonth): bool
    {
        return Invoice::query()
            ->where('student_id', $fee->student_id)
            ->where('academic_year_id', $fee->academic_year_id)
            ->where('status', '!=', InvoiceStatus::Void->value)
            ->when($periodMonth !== null,
                fn ($query) => $query->where('period_month', $periodMonth),
                fn ($query) => $query->whereNull('period_month'))
            ->whereHas('items', fn ($query) => $query
                ->where('item_type', 'posisi')
                ->where('fee_type_id', $fee->fee_type_id))
            ->exists();
    }

    /**
     * SPP due date: the 10th of the billed month; one-off fees: 30 days.
     */
    private function dueDate(AcademicYear $year, ?int $periodMonth): Carbon
    {
        if ($periodMonth === null) {
            return Carbon::today()->addDays(30);
        }

        $calendarYear = $periodMonth >= $year->starts_at->month
            ? $year->starts_at->year
            : $year->starts_at->year + 1;

        return Carbon::create($calendarYear, $periodMonth, 10);
    }

    private function periodLabel(AcademicYear $year, ?int $periodMonth): string
    {
        return $periodMonth !== null
            ? InvoiceService::monthLabel($year, $periodMonth)
            : '';
    }
}
