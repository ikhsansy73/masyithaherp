<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Models\AcademicYear;
use App\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Accounting periods are seeded automatically whenever an academic year is
 * created (AcademicYearService::seedTermsAndPeriods), so creating one via
 * this factory directly would collide with the auto-seeded rows (unique
 * name). Do not instantiate periods through this factory — create the
 * academic year and read its periods:
 *
 *   $year = AcademicYear::factory()->forStartYear(2026)->create();
 *   $period = $year->periods()->where('name', '2026-08')->first();
 *
 * This factory exists only to satisfy the model's HasFactory contract.
 *
 * @extends Factory<AccountingPeriod>
 */
class AccountingPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => sprintf('%04d-%02d', now()->year, now()->month),
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
            'status' => PeriodStatus::Open,
        ];
    }
}
