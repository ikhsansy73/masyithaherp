<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'INV/'.fake()->unique()->numerify('########'),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'academic_term_id' => null,
            'invoice_batch_id' => null,
            'invoice_date' => today(),
            'due_date' => today()->addDays(30),
            'period_month' => null,
            'description' => 'Tagihan',
            'status' => InvoiceStatus::Draft,
            'total' => 600_000,
            'paid_amount' => 0,
            'fund_id' => null,
            'source' => 'batch',
            'voided_reason' => null,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (): array => ['status' => InvoiceStatus::Issued]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Paid,
            'paid_amount' => $attributes['total'],
        ]);
    }

    public function dueOn(\Illuminate\Support\Carbon $date): static
    {
        return $this->state(fn (): array => ['due_date' => $date->copy()]);
    }
}
