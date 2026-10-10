<?php

namespace App\Enums;

use App\Settings\AcademicSettings;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Kurikulum Merdeka rubric predicate (doc 06 §6): SB ≥ 90, BSH ≥ 80,
 * MB ≥ 70, BB < 70 (thresholds in AcademicSettings).
 */
enum LearningPredicate: string implements HasColor, HasLabel
{
    case BB = 'BB';

    case MB = 'MB';

    case BSH = 'BSH';

    case SB = 'SB';

    public function getLabel(): string
    {
        return match ($this) {
            self::BB => 'BB — Perlu Bimbingan',
            self::MB => 'MB — Berkembang',
            self::BSH => 'BSH — Cakap',
            self::SB => 'SB — Mahir',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BB => 'danger',
            self::MB => 'warning',
            self::BSH => 'info',
            self::SB => 'success',
        };
    }

    /**
     * Numeric equivalent used when a Fase A assessment is recorded as a
     * rubric predicate instead of a number — the middle of each threshold
     * band, so rubric-only subjects still produce a 40/60 final score.
     */
    public function numericEquivalent(): float
    {
        return match ($this) {
            self::BB => 65.0,
            self::MB => 75.0,
            self::BSH => 85.0,
            self::SB => 95.0,
        };
    }

    /**
     * Predicate for a numeric score using the AcademicSettings thresholds
     * (SB ≥ 90, BSH ≥ 80, MB ≥ 70, BB below).
     */
    public static function fromScore(float $score): self
    {
        $settings = app(AcademicSettings::class);

        return match (true) {
            $score >= $settings->threshold_sb => self::SB,
            $score >= $settings->threshold_bsh => self::BSH,
            $score >= $settings->threshold_mb => self::MB,
            default => self::BB,
        };
    }
}
