<?php

namespace App\Enums;

enum GuardianEducation: string
{
    case Sd = 'sd';
    case Smp = 'smp';
    case Sma = 'sma';
    case D1 = 'd1';
    case D2 = 'd2';
    case D3 = 'd3';
    case D4 = 'd4';
    case S1 = 's1';
    case S2 = 's2';
    case S3 = 's3';

    public function label(): string
    {
        return match ($this) {
            self::Sd => 'SD',
            self::Smp => 'SMP',
            self::Sma => 'SMA/SMK',
            self::D1 => 'D1',
            self::D2 => 'D2',
            self::D3 => 'D3',
            self::D4 => 'D4/Sarjana Muda',
            self::S1 => 'S1',
            self::S2 => 'S2',
            self::S3 => 'S3',
        };
    }
}
