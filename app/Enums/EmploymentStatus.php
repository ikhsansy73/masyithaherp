<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Pns = 'pns';
    case Pppk = 'pppk';
    case Tetap = 'tetap';
    case Honorer = 'honorer';
    case Bsm = 'bsm';

    public function label(): string
    {
        return match ($this) {
            self::Pns => 'PNS',
            self::Pppk => 'PPPK',
            self::Tetap => 'Tetap',
            self::Honorer => 'Honorer',
            self::Bsm => 'BSM (Yayasan)',
        };
    }
}
