<?php

namespace Database\Factories;

use App\Models\LearningAchievement;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningAchievement>
 */
class LearningAchievementFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        return [
            'subject_id' => Subject::factory(),
            'fase' => 'A',
            'elemen' => fake()->randomElement(['Bilangan', 'Literasi', 'Alam', 'Kewargaan']),
            'code' => 'A.'.self::$sequence,
            'description' => fake()->sentence(12),
            'is_active' => true,
        ];
    }

    public function fase(string $fase): static
    {
        return $this->state(fn (): array => ['fase' => $fase]);
    }
}
