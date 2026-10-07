<?php

namespace App\Enums;

enum SubjectKelompok: string
{
    case A = 'A';
    case B = 'B';

    public function label(): string
    {
        return match ($this) {
            self::A => 'Kelompok A',
            self::B => 'Kelompok B',
        };
    }
}
