<?php

namespace App\Enums;

/**
 * Consumable stock directions (doc 07 §6). masuk = purchase into 1-1400,
 * keluar = issuance expensed at weighted-average cost.
 */
enum StockMovementType: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';

    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Masuk => 'success',
            self::Keluar => 'warning',
        };
    }
}
