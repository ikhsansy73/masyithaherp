<?php

namespace Database\Factories;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        $startYear = 2025 + self::$sequence;

        return [
            'name' => $startYear.'/'.($startYear + 1),
            'starts_at' => CarbonImmutable::create($startYear, 7, 1),
            'ends_at' => CarbonImmutable::create($startYear + 1, 6, 30),
            'status' => AcademicYearStatus::Planned,
            'is_default' => false,
        ];
    }

    /**
     * Point the year at a specific starting year (e.g. 2026 → "2026/2027").
     */
    public function forStartYear(int $startYear): static
    {
        return $this->state(fn (): array => [
            'name' => $startYear.'/'.($startYear + 1),
            'starts_at' => CarbonImmutable::create($startYear, 7, 1),
            'ends_at' => CarbonImmutable::create($startYear + 1, 6, 30),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => AcademicYearStatus::Active,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'status' => AcademicYearStatus::Active,
            'is_default' => true,
        ]);
    }
}
