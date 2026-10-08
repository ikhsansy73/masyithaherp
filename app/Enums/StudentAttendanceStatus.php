<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentAttendanceStatus: string implements HasColor, HasLabel
{
    case Hadir = 'hadir';
    case Sakit = 'sakit';
    case Izin = 'izin';
    case Alpa = 'alpa';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Sakit => 'Sakit',
            self::Izin => 'Izin',
            self::Alpa => 'Alpa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Hadir => 'success',
            self::Sakit => 'warning',
            self::Izin => 'info',
            self::Alpa => 'danger',
        };
    }
}
