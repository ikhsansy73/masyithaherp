<?php

namespace App\Enums;

enum StudentAttendanceStatus: string
{
    case Hadir = 'hadir';
    case Sakit = 'sakit';
    case Izin = 'izin';
    case Alpa = 'alpa';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Sakit => 'Sakit',
            self::Izin => 'Izin',
            self::Alpa => 'Alpa',
        };
    }
}
