<?php

namespace App\Enums;

enum JournalSource: string
{
    case Manual = 'manual';
    case Otomatis = 'otomatis';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Otomatis => 'Otomatis',
        };
    }
}
