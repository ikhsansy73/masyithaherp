<?php

namespace App\Enums;

enum SalaryComponentType: string
{
    case Pendapatan = 'pendapatan';
    case Potongan = 'potongan';

    public function label(): string
    {
        return match ($this) {
            self::Pendapatan => 'Pendapatan',
            self::Potongan => 'Potongan',
        };
    }
}
