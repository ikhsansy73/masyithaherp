<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentScore>
 */
class AssessmentScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => Student::factory(),
            'score' => fake()->numberBetween(60, 100),
            'predicate' => null,
            'note' => null,
        ];
    }

    public function rubric(): static
    {
        return $this->state(fn (): array => [
            'score' => null,
            'predicate' => fake()->randomElement(['BB', 'MB', 'BSH', 'SB']),
        ]);
    }
}
