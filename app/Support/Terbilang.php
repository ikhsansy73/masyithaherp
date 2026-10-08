<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Spelled-out rupiah amounts for kwitansi and letters
 * ("Seratus lima puluh ribu Rupiah").
 */
final class Terbilang
{
    private const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function make(int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Terbilang tidak mendukung nilai negatif.');
        }

        if ($amount === 0) {
            return 'Nol Rupiah';
        }

        return ucfirst(self::spell($amount)).' Rupiah';
    }

    private static function spell(int $amount): string
    {
        if ($amount < 12) {
            return self::SATUAN[$amount];
        }

        if ($amount < 20) {
            return trim(self::SATUAN[$amount - 10].' belas');
        }

        if ($amount < 100) {
            return trim(self::SATUAN[intdiv($amount, 10)].' puluh '.self::SATUAN[$amount % 10]);
        }

        if ($amount < 200) {
            return trim('seratus '.self::spell($amount - 100));
        }

        if ($amount < 1000) {
            return trim(self::SATUAN[intdiv($amount, 100)].' ratus '.self::spell($amount % 100));
        }

        if ($amount < 2000) {
            return trim('seribu '.self::spell($amount - 1000));
        }

        if ($amount < 1_000_000) {
            return trim(self::spell(intdiv($amount, 1000)).' ribu '.self::spell($amount % 1000));
        }

        if ($amount < 1_000_000_000) {
            return trim(self::spell(intdiv($amount, 1_000_000)).' juta '.self::spell($amount % 1_000_000));
        }

        if ($amount < 1_000_000_000_000) {
            return trim(self::spell(intdiv($amount, 1_000_000_000)).' miliar '.self::spell($amount % 1_000_000_000));
        }

        return trim(self::spell(intdiv($amount, 1_000_000_000_000)).' triliun '.self::spell($amount % 1_000_000_000_000));
    }
}
