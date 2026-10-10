<?php

namespace App\Enums;

/**
 * Physical condition of an asset, reconfirmed at opname (doc 02 §7).
 */
enum AssetCondition: string
{
    case Baik = 'baik';
    case RusakRingan = 'rusak_ringan';
    case RusakBerat = 'rusak_berat';

    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::RusakRingan => 'Rusak Ringan',
            self::RusakBerat => 'Rusak Berat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baik => 'success',
            self::RusakRingan => 'warning',
            self::RusakBerat => 'danger',
        };
    }
}
