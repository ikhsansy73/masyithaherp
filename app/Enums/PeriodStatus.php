<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Closed => 'Ditutup',
        };
    }
}
