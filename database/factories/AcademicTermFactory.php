<?php

namespace Database\Factories;

use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'number' => 1,
            'name' => 'Semester 1 — Ganjil',
            'starts_at' => CarbonImmutable::create(2026, 7, 1),
            'ends_at' => CarbonImmutable::create(2026, 12, 31),
            'status' => TermStatus::Planned,
        ];
    }

    /**
     * Semester 2 (genap): January–June of the following year.
     */
    public function genap(): static
    {
        return $this->state(fn (): array => [
            'number' => 2,
            'name' => 'Semester 2 — Genap',
            'starts_at' => CarbonImmutable::create(2027, 1, 1),
            'ends_at' => CarbonImmutable::create(2027, 6, 30),
        ]);
    }
}
