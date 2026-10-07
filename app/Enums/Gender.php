<?php

namespace App\Enums;

enum Gender: string
{
    case L = 'L';
    case P = 'P';

    public function label(): string
    {
        return match ($this) {
            self::L => 'Laki-laki',
            self::P => 'Perempuan',
        };
    }
}
