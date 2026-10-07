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
    public function definition(): array
    {
        return [
            'name' => '2026/2027',
            'starts_at' => CarbonImmutable::create(2026, 7, 1),
            'ends_at' => CarbonImmutable::create(2027, 6, 30),
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
