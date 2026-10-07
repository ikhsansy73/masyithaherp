<?php

namespace App\Enums;

enum AccountType: string
{
    case Aset = 'aset';
    case Kewajiban = 'kewajiban';
    case Ekuitas = 'ekuitas';
    case Pendapatan = 'pendapatan';
    case Beban = 'beban';

    public function label(): string
    {
        return match ($this) {
            self::Aset => 'Aset',
            self::Kewajiban => 'Kewajiban',
            self::Ekuitas => 'Ekuitas',
            self::Pendapatan => 'Pendapatan',
            self::Beban => 'Beban',
        };
    }
}
