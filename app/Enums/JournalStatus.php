<?php

namespace App\Enums;

enum JournalStatus: string
{
    case Posted = 'posted';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Posted => 'Diposting',
            self::Void => 'Dibatalkan',
        };
    }
}
