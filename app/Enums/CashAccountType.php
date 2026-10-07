<?php

namespace App\Enums;

enum CashAccountType: string
{
    case Kas = 'kas';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Kas => 'Kas',
            self::Bank => 'Bank',
        };
    }
}
