<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tunggakan query + aging buckets (doc 04 §5): invoices issued/
 * partially_paid past due, bucketed 1-30 / 31-60 / 61-90 / >90 days,
 * grouped per student with discount flags.
 */
class ArrearsService
{
    /**
     * Per-student arrears rows, oldest overdue first.
     *
     * @return Collection<int, array{
     *     student: Student,
     *     classroom: ?string,
     *     grade: ?int,
     *     total: int,
     *     oldest_due: ?Carbon,
     *     days_overdue: int,
     *     buckets: array{0: int, 1: int, 2: int, 3: int},
     *     has_discount: bool,
     *     invoice_ids: list<int>,
     * }>
     */
    public function arrears(?AcademicYear $year = null): Collection
    {
        $year ??= AcademicYear::query()->where('is_default', true)->first();

        if ($year === null) {
            return collect();
        }

        $invoices = Invoice::query()
            ->where('academic_year_id', $year->getKey())
            ->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->with(['student.enrollments' => fn ($query) => $query
                ->where('academic_year_id', $year->getKey())
                ->with('classroom:id,name,grade_level'),
            ])
            ->with(['student.discounts' => fn ($query) => $query->where('is_active', true)])
            ->get();

        return $invoices
            ->groupBy('student_id')
            ->map(function ($studentInvoices): array {
                /** @var Student $student */
                $student = $studentInvoices->first()->student;

                $today = today();
                $oldest = $studentInvoices->sortBy('due_date')->first();

                $buckets = [0, 0, 0, 0];

                foreach ($studentInvoices as $invoice) {
                    $days = (int) abs($today->diffInDays($invoice->due_date));
                    $remaining = $invoice->remainingAmount();

                    $index = match (true) {
                        $days <= 30 => 0,
                        $days <= 60 => 1,
                        $days <= 90 => 2,
                        default => 3,
                    };

                    $buckets[$index] += $remaining;
                }

                $enrollment = $student->enrollments->first();

                return [
                    'student' => $student,
                    'classroom' => $enrollment?->classroom?->name,
                    'grade' => $enrollment?->classroom?->grade_level,
                    'total' => (int) $studentInvoices->sum(fn (Invoice $invoice): int => $invoice->remainingAmount()),
                    'oldest_due' => $oldest->due_date,
                    'days_overdue' => (int) abs($today->diffInDays($oldest->due_date)),
                    'buckets' => $buckets,
                    'has_discount' => $student->discounts->isNotEmpty(),
                    'invoice_ids' => $studentInvoices->pluck('id')->all(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Grand total across all students.
     */
    public function total(?AcademicYear $year = null): int
    {
        return (int) $this->arrears($year)->sum('total');
    }

    /**
     * @return Collection<int, Payment>
     */
    public function unallocatedPayments(): Collection
    {
        return Payment::query()
            ->whereNull('reversed_by_payment_id')
            ->with('student:id,full_name')
            ->get()
            ->filter(fn (Payment $payment): bool => $payment->unallocatedAmount() > 0)
            ->values();
    }
}
