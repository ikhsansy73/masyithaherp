<?php

namespace App\Models;

use App\Enums\LearningPredicate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Per-subject final score + predicate + editable description on a rapor. */
class ReportCardSubject extends Model
{
    /** @use HasFactory<\Database\Factories\ReportCardSubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'report_card_id',
        'subject_id',
        'final_score',
        'predicate',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'predicate' => LearningPredicate::class,
        ];
    }

    /**
     * @return BelongsTo<ReportCard, $this>
     */
    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
