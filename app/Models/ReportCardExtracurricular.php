<?php

namespace App\Models;

use App\Enums\LearningPredicate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ekstrakurikuler entry on a rapor. */
class ReportCardExtracurricular extends Model
{
    /** @use HasFactory<\Database\Factories\ReportCardExtracurricularFactory> */
    use HasFactory;

    protected $fillable = [
        'report_card_id',
        'extracurricular_id',
        'predicate',
        'description',
    ];

    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<Extracurricular, $this>
     */
    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }
}
