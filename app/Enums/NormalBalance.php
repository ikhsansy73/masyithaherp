<?php

namespace App\Enums;

enum NormalBalance: string
{
    case Debit = 'debit';
    case Kredit = 'kredit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'Debit',
            self::Kredit => 'Kredit',
        };
    }
}
