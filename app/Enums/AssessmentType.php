<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssessmentType: string implements HasColor, HasLabel
{
    case Formatif = 'formatif';

    case Sumatif = 'sumatif';

    case SumatifAkhir = 'sumatif_akhir';

    public function getLabel(): string
    {
        return match ($this) {
            self::Formatif => 'Formatif',
            self::Sumatif => 'Sumatif',
            self::SumatifAkhir => 'Sumatif Akhir',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Formatif => 'info',
            self::Sumatif => 'warning',
            self::SumatifAkhir => 'danger',
        };
    }
}
