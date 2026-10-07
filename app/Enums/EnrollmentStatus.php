<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Aktif = 'aktif';
    case Pindah = 'pindah';
    case Keluar = 'keluar';
    case Lulus = 'lulus';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Pindah => 'Pindah',
            self::Keluar => 'Keluar',
            self::Lulus => 'Lulus',
        };
    }
}
