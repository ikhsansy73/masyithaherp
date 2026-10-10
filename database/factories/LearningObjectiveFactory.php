<?php

namespace Database\Factories;

use App\Models\LearningAchievement;
use App\Models\LearningObjective;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningObjective>
 */
class LearningObjectiveFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        return [
            'learning_achievement_id' => LearningAchievement::factory(),
            'code' => 'A.'.self::$sequence.'.1',
            'description' => fake()->sentence(10),
            'semester' => 1,
            'sequence' => 1,
        ];
    }

    public function semester(int $semester, int $sequence = 1): static
    {
        return $this->state(fn (): array => [
            'semester' => $semester,
            'sequence' => $sequence,
        ]);
    }
}
