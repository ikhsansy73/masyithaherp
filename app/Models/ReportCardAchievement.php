<?php

namespace App\Models;

use App\Enums\AchievementLevel;
use App\Enums\AchievementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Prestasi entry on a rapor. */
class ReportCardAchievement extends Model
{
    /** @use HasFactory<\Database\Factories\ReportCardAchievementFactory> */
    use HasFactory;

    protected $fillable = [
        'report_card_id',
        'name',
        'type',
        'level',
        'rank',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => AchievementType::class,
            'level' => AchievementLevel::class,
            'rank' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ReportCard, $this>
     */
    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }
}
