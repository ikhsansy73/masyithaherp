<?php

namespace Database\Factories;

use App\Enums\LearningPredicate;
use App\Models\Extracurricular;
use App\Models\ReportCard;
use App\Models\ReportCardExtracurricular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCardExtracurricular>
 */
class ReportCardExtracurricularFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_card_id' => ReportCard::factory(),
            'extracurricular_id' => Extracurricular::factory(),
            'predicate' => fake()->randomElement([LearningPredicate::BSH, LearningPredicate::SB, LearningPredicate::MB]),
            'description' => fake()->optional()->sentence(6),
        ];
    }
}
