<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AchievementType: string implements HasLabel
{
    case Akademik = 'akademik';

    case NonAkademik = 'non_akademik';

    public function getLabel(): string
    {
        return match ($this) {
            self::Akademik => 'Akademik',
            self::NonAkademik => 'Non-Akademik',
        };
    }
}
