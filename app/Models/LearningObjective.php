<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** TP (Tujuan Pembelajaran) authored by teachers under each CP. */
class LearningObjective extends Model
{
    /** @use HasFactory<\Database\Factories\LearningObjectiveFactory> */
    use HasFactory;

    protected $fillable = [
        'learning_achievement_id',
        'code',
        'description',
        'semester',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'sequence' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LearningAchievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(LearningAchievement::class);
    }

    /**
     * @return HasMany<TeacherLearningJournal, $this>
     */
    public function journals(): HasMany
    {
        return $this->hasMany(TeacherLearningJournal::class);
    }

    /**
     * @return HasMany<Assessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
