<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Models\AccountingPeriod;
use App\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingPeriod>
 */
class AccountingPeriodFactory extends Factory
{
    public function definition(): array
    {
        $month = CarbonImmutable::create(2026, 7, 1);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => $month->format('Y-m'),
            'starts_at' => $month,
            'ends_at' => $month->endOfMonth(),
            'status' => PeriodStatus::Open,
        ];
    }

    public function forMonth(int $year, int $month): static
    {
        return $this->state(fn (): array => [
            'name' => sprintf('%04d-%02d', $year, $month),
            'starts_at' => CarbonImmutable::create($year, $month, 1),
            'ends_at' => CarbonImmutable::create($year, $month, 1)->endOfMonth(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => PeriodStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
