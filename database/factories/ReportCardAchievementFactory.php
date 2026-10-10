<?php

namespace Database\Factories;

use App\Enums\AchievementLevel;
use App\Enums\AchievementType;
use App\Models\ReportCard;
use App\Models\ReportCardAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCardAchievement>
 */
class ReportCardAchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_card_id' => ReportCard::factory(),
            'name' => 'Juara '.fake()->numberBetween(1, 3).' '.fake()->words(2, true),
            'type' => AchievementType::Akademik,
            'level' => AchievementLevel::Kecamatan,
            'rank' => fake()->numberBetween(1, 3),
            'description' => fake()->optional()->sentence(6),
        ];
    }
}
