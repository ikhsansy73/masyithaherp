<?php

namespace App\Enums;

enum CashFlowCategory: string
{
    case Operasi = 'operasi';
    case Investasi = 'investasi';
    case Pendanaan = 'pendanaan';
    case NonKas = 'non_kas';

    public function label(): string
    {
        return match ($this) {
            self::Operasi => 'Operasi',
            self::Investasi => 'Investasi',
            self::Pendanaan => 'Pendanaan',
            self::NonKas => 'Non Kas',
        };
    }
}
