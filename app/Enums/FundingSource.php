<?php

namespace App\Enums;

/**
 * Descriptive funding-source label on the asset registry (doc 07 §1).
 * The accounting dimension is fund_id; this label is detail only.
 */
enum FundingSource: string
{
    case Bos = 'bos';
    case Yayasan = 'yayasan';
    case Komite = 'komite';
    case Hibah = 'hibah';

    public function label(): string
    {
        return match ($this) {
            self::Bos => 'BOS',
            self::Yayasan => 'Yayasan',
            self::Komite => 'Komite',
            self::Hibah => 'Hibah',
        };
    }
}
