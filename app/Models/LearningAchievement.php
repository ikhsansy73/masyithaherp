<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** CP (Capaian Pembelajaran) per subject × fase, from the BSKAP documents. */
class LearningAchievement extends Model
{
    /** @use HasFactory<\Database\Factories\LearningAchievementFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'fase',
        'elemen',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return HasMany<LearningObjective, $this>
     */
    public function objectives(): HasMany
    {
        return $this->hasMany(LearningObjective::class)
            ->orderBy('semester')
            ->orderBy('sequence');
    }
}
