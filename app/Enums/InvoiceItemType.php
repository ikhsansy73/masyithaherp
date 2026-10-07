<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Posisi = 'posisi';
    case Potongan = 'potongan';

    public function label(): string
    {
        return match ($this) {
            self::Posisi => 'Biaya',
            self::Potongan => 'Potongan',
        };
    }
}
