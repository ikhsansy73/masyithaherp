<?php

namespace App\Enums;

enum PpdbStatus: string
{
    case Baru = 'baru';
    case Verifikasi = 'verifikasi';
    case Diterima = 'diterima';
    case Cadangan = 'cadangan';
    case Ditolak = 'ditolak';
    case Terdaftar = 'terdaftar';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Verifikasi => 'Diverifikasi',
            self::Diterima => 'Diterima',
            self::Cadangan => 'Cadangan',
            self::Ditolak => 'Ditolak',
            self::Terdaftar => 'Terdaftar',
        };
    }
}
