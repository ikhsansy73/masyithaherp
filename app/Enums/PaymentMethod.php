<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Tunai = 'tunai';
    case Transfer = 'transfer';
    case Qris = 'qris';
    case Ewallet = 'ewallet';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Transfer => 'Transfer',
            self::Qris => 'QRIS',
            self::Ewallet => 'E-Wallet',
            self::Lainnya => 'Lainnya',
        };
    }
}
