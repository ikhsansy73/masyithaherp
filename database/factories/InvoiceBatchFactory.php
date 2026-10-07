<?php

namespace Database\Factories;

use App\Enums\InvoiceBatchStatus;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\InvoiceBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceBatch>
 */
class InvoiceBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'fee_type_id' => FeeType::factory(),
            'period_month' => 7,
            'grade_filter' => null,
            'total_invoices' => 0,
            'total_amount' => 0,
            'status' => InvoiceBatchStatus::Draft,
            'generated_by' => null,
            'generated_at' => null,
            'journal_entry_id' => null,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceBatchStatus::Issued,
            'generated_at' => now(),
        ]);
    }
}
