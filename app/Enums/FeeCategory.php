<?php

namespace App\Enums;

enum FeeCategory: string
{
    case Bulanan = 'bulanan';
    case Tahunan = 'tahunan';
    case Insidental = 'insidental';

    public function label(): string
    {
        return match ($this) {
            self::Bulanan => 'Bulanan',
            self::Tahunan => 'Tahunan',
            self::Insidental => 'Insidental',
        };
    }

    /**
     * The number of student_fees months this category covers (doc 02).
     */
    public function defaultMonths(): int
    {
        return match ($this) {
            self::Bulanan => 12,
            self::Tahunan, self::Insidental => 1,
        };
    }
}
