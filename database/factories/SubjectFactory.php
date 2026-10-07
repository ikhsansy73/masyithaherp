<?php

namespace Database\Factories;

use App\Enums\SubjectKelompok;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->words(2, true),
            'kelompok' => SubjectKelompok::A,
            'jp_per_week' => 2,
            'is_active' => true,
        ];
    }
}
