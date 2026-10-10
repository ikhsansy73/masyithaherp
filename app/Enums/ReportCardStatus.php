<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportCardStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';

    case Diajukan = 'diajukan';

    case Revisi = 'revisi';

    case Disetujui = 'disetujui';

    case Diterbitkan = 'diterbitkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Diajukan => 'Diajukan',
            self::Revisi => 'Perlu Revisi',
            self::Disetujui => 'Disetujui',
            self::Diterbitkan => 'Diterbitkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Diajukan => 'warning',
            self::Revisi => 'danger',
            self::Disetujui => 'info',
            self::Diterbitkan => 'success',
        };
    }
}
