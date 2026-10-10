<?php

namespace App\Enums;

/**
 * Asset lifecycle (doc 07 §1): aktif → dijual / dihapuskan / hilang.
 * Only aktif assets keep depreciating and can be disposed of.
 */
enum AssetStatus: string
{
    case Aktif = 'aktif';
    case Dijual = 'dijual';
    case Dihapuskan = 'dihapuskan';
    case Hilang = 'hilang';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Dijual => 'Dijual',
            self::Dihapuskan => 'Dihapuskan',
            self::Hilang => 'Hilang',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Dijual => 'info',
            self::Dihapuskan => 'gray',
            self::Hilang => 'danger',
        };
    }

    /**
     * Aktif is the only state that can still be disposed of or depreciated.
     */
    public function isDisposable(): bool
    {
        return $this === self::Aktif;
    }
}
