<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AssessmentDimension: string implements HasLabel
{
    case Pengetahuan = 'pengetahuan';

    case Keterampilan = 'keterampilan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pengetahuan => 'Pengetahuan',
            self::Keterampilan => 'Keterampilan',
        };
    }
}
