<?php

namespace App\Enums;

enum SalaryCalculation: string
{
    case Fixed = 'fixed';
    case PercentBase = 'percent_base';
    case ManualEntry = 'manual_entry';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Nominal Tetap',
            self::PercentBase => 'Persen Gaji Pokok',
            self::ManualEntry => 'Entri Manual',
        };
    }
}
