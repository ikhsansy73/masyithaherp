<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Academic computation settings (doc 06 §6–7). */
class AcademicSettings extends Settings
{
    /** Final score = formative_weight% × avg(formatif) + summative_weight% × avg(sumatif). */
    public int $formative_weight;

    public int $summative_weight;

    /** Predicate thresholds: SB ≥ threshold_sb, BSH ≥ threshold_bsh, MB ≥ threshold_mb, else BB. */
    public int $threshold_sb;

    public int $threshold_bsh;

    public int $threshold_mb;

    /** TP mean at or above this counts as mastered when drafting rapor descriptions. */
    public int $tp_mastery_threshold;

    public static function group(): string
    {
        return 'academic';
    }
}
