<?php

namespace Database\Factories;

use App\Enums\LearningPredicate;
use App\Models\ReportCard;
use App\Models\ReportCardSubject;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCardSubject>
 */
class ReportCardSubjectFactory extends Factory
{
    public function definition(): array
    {
        $score = fake()->numberBetween(65, 95);

        return [
            'report_card_id' => ReportCard::factory(),
            'subject_id' => Subject::factory(),
            'final_score' => $score,
            'predicate' => LearningPredicate::fromScore($score),
            'description' => fake()->sentence(12),
        ];
    }
}
