<?php

namespace App\Enums;

enum FundType: string
{
    case Pemerintah = 'pemerintah';
    case Yayasan = 'yayasan';
    case Komite = 'komite';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Pemerintah => 'Pemerintah',
            self::Yayasan => 'Yayasan',
            self::Komite => 'Komite',
            self::Lainnya => 'Lainnya',
        };
    }
}
