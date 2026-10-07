<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Aktif = 'aktif';
    case Lulus = 'lulus';
    case MutasiKeluar = 'mutasi_keluar';
    case Keluar = 'keluar';
    case Cadangan = 'cadangan';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Lulus => 'Lulus',
            self::MutasiKeluar => 'Mutasi Keluar',
            self::Keluar => 'Keluar',
            self::Cadangan => 'Cadangan',
        };
    }
}
