<?php

namespace App\Services\Academics;

use App\Enums\LearningPredicate;
use App\Settings\AcademicSettings;

/**
 * Final subject score (doc 06 §6): 40% × avg(formatif) + 60% × avg(sumatif)
 * with weights from AcademicSettings. Rubric-only inputs (Fase A BB/MB/
 * BSH/SB) enter the averages through their numeric equivalents.
 */
class FinalScoreCalculator
{
    public function __construct(
        private readonly AcademicSettings $settings,
    ) {}

    /**
     * @param  iterable<int, float|null>  $formativeScores
     * @param  iterable<int, float|null>  $summativeScores
     */
    public function compute(iterable $formativeScores, iterable $summativeScores): ?float
    {
        $formative = $this->average($formativeScores);
        $summative = $this->average($summativeScores);

        if ($formative === null && $summative === null) {
            return null;
        }

        $formativeWeight = $formative === null ? 0 : $this->settings->formative_weight / 100;
        $summativeWeight = $summative === null ? 0 : $this->settings->summative_weight / 100;

        if ($formativeWeight + $summativeWeight === 0.0) {
            return null;
        }

        return ($formativeWeight * $formative + $summativeWeight * $summative)
            / ($formativeWeight + $summativeWeight);
    }

    /**
     * Predicate for a final score from the settings thresholds.
     */
    public function predicate(float $score): LearningPredicate
    {
        return LearningPredicate::fromScore($score);
    }

    /**
     * Mean of non-null inputs; null when there is nothing to average.
     *
     * @param  iterable<int, float|null>  $scores
     */
    private function average(iterable $scores): ?float
    {
        $values = collect($scores)
            ->filter(fn ($score): bool => $score !== null)
            ->map(fn ($score): float => (float) $score)
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        return $values->sum() / $values->count();
    }
}
