<?php

namespace App\Enums;

enum MovementType: string
{
    case Kenaikan = 'kenaikan';
    case TinggalKelas = 'tinggal_kelas';
    case Lulus = 'lulus';
    case MutasiMasuk = 'mutasi_masuk';
    case MutasiKeluar = 'mutasi_keluar';
    case Keluar = 'keluar';

    public function label(): string
    {
        return match ($this) {
            self::Kenaikan => 'Kenaikan Kelas',
            self::TinggalKelas => 'Tinggal Kelas',
            self::Lulus => 'Lulus',
            self::MutasiMasuk => 'Mutasi Masuk',
            self::MutasiKeluar => 'Mutasi Keluar',
            self::Keluar => 'Keluar',
        };
    }
}
