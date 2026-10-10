<?php

namespace Tests\Unit;

use App\Enums\LearningPredicate;
use App\Services\Academics\FinalScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalScoreComputationTest extends TestCase
{
    use RefreshDatabase;

    private FinalScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(FinalScoreCalculator::class);
    }

    public function test_final_is_40_percent_formative_plus_60_percent_summative(): void
    {
        $final = $this->calculator->compute([80, 90], [70, 80]);

        // 40% × avg(80, 90)=85 + 60% × avg(70, 80)=75 → 34 + 45 = 79
        $this->assertSame(79.0, $final);
    }

    public function test_missing_formative_scores_fall_back_to_summative_only(): void
    {
        $this->assertSame(80.0, $this->calculator->compute([], [80]));
        $this->assertSame(85.0, $this->calculator->compute([null], [85]));
    }

    public function test_missing_summative_scores_fall_back_to_formative_only(): void
    {
        $this->assertSame(90.0, $this->calculator->compute([90], []));
    }

    public function test_no_scores_at_all_returns_null(): void
    {
        $this->assertNull($this->calculator->compute([], []));
        $this->assertNull($this->calculator->compute([null, null], [null]));
    }

    public function test_predicate_thresholds_match_settings(): void
    {
        $this->assertSame(LearningPredicate::SB, $this->calculator->predicate(90));
        $this->assertSame(LearningPredicate::BSH, $this->calculator->predicate(80));
        $this->assertSame(LearningPredicate::BSH, $this->calculator->predicate(89.99));
        $this->assertSame(LearningPredicate::MB, $this->calculator->predicate(70));
        $this->assertSame(LearningPredicate::BB, $this->calculator->predicate(69.5));
    }

    public function test_rubric_numeric_equivalents_are_band_midpoints(): void
    {
        $this->assertSame(65.0, LearningPredicate::BB->numericEquivalent());
        $this->assertSame(75.0, LearningPredicate::MB->numericEquivalent());
        $this->assertSame(85.0, LearningPredicate::BSH->numericEquivalent());
        $this->assertSame(95.0, LearningPredicate::SB->numericEquivalent());
    }
}
