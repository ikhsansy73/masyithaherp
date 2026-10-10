<?php

namespace App\Enums;

/**
 * Consumable stock units (doc 02 §7): pcs, rim, box, lusin.
 */
enum InventoryUnit: string
{
    case Pcs = 'pcs';
    case Rim = 'rim';
    case Box = 'box';
    case Lusin = 'lusin';

    public function label(): string
    {
        return $this->value;
    }
}
