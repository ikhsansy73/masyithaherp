<?php

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    public function definition(): array
    {
        $year = (int) fake()->numberBetween(2025, 2027);
        $month = (int) fake()->numberBetween(1, 12);

        return [
            'name' => sprintf('Payroll %d-%02d', $year, $month),
            'period_year' => $year,
            'period_month' => $month,
            'status' => PayrollStatus::Draft,
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollStatus::Calculated,
            'calculated_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollStatus::Approved,
            'calculated_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollStatus::Paid,
            'calculated_at' => now(),
            'approved_at' => now(),
            'paid_at' => now(),
        ]);
    }
}
