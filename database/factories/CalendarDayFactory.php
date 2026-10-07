<?php

namespace Database\Factories;

use App\Enums\CalendarDayType;
use App\Models\CalendarDay;
use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarDay>
 */
class CalendarDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->date(),
            'academic_term_id' => AcademicTerm::factory(),
            'type' => CalendarDayType::Efektif,
            'description' => null,
        ];
    }

    public function libur(string $description = null): static
    {
        return $this->state(fn (): array => [
            'type' => CalendarDayType::Libur,
            'description' => $description,
        ]);
    }
}
