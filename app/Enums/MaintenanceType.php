<?php

namespace App\Enums;

/**
 * Asset maintenance kinds (doc 07 §4). Both expense to 5-1800.
 */
enum MaintenanceType: string
{
    case Perawatan = 'perawatan';
    case Perbaikan = 'perbaikan';

    public function label(): string
    {
        return match ($this) {
            self::Perawatan => 'Perawatan',
            self::Perbaikan => 'Perbaikan',
        };
    }
}
