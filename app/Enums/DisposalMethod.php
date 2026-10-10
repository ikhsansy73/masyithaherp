<?php

namespace App\Enums;

/**
 * How an asset left the registry (doc 07 §3). dijual books proceeds and
 * possibly a gain; dihapuskan/hilang write the remaining NBV off as a loss.
 */
enum DisposalMethod: string
{
    case Dijual = 'dijual';
    case Dihapuskan = 'dihapuskan';
    case Hilang = 'hilang';

    public function label(): string
    {
        return match ($this) {
            self::Dijual => 'Dijual',
            self::Dihapuskan => 'Dihapuskan',
            self::Hilang => 'Hilang',
        };
    }

    /**
     * dijual is rule #14 (with proceeds); the others are rule #15.
     */
    public function hasProceeds(): bool
    {
        return $this === self::Dijual;
    }
}
