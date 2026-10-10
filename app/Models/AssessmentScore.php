<?php

namespace App\Models;

use App\Enums\LearningPredicate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's score on one assessment: numeric `score` OR rubric
 * `predicate` (BB/MB/BSH/SB, Fase A) — service validation enforces that
 * one of the two is present.
 */
class AssessmentScore extends Model
{
    /** @use HasFactory<\Database\Factories\AssessmentScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'student_id',
        'score',
        'predicate',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'predicate' => LearningPredicate::class,
        ];
    }

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The numeric value this row contributes to averages: the score
     * itself, or the rubric band's numeric equivalent.
     */
    public function numericValue(): float
    {
        if ($this->score !== null) {
            return (float) $this->score;
        }

        return ($this->predicate ?? LearningPredicate::BB)->numericEquivalent();
    }
}
