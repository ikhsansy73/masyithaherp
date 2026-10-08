<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function amountProvider(): array
    {
        return [
            'zero' => [0, 'Nol Rupiah'],
            'one' => [1, 'Satu Rupiah'],
            'eleven' => [11, 'Sebelas Rupiah'],
            'twelve' => [12, 'Dua belas Rupiah'],
            'twenty' => [20, 'Dua puluh Rupiah'],
            'hundred' => [100, 'Seratus Rupiah'],
            'one-fifty' => [150, 'Seratus lima puluh Rupiah'],
            'one-thousand' => [1000, 'Seribu Rupiah'],
            'two-thousand-five' => [2500, 'Dua ribu lima ratus Rupiah'],
            'spp' => [600_000, 'Enam ratus ribu Rupiah'],
            'kwitansi' => [1_250_500, 'Satu juta dua ratus lima puluh ribu lima ratus Rupiah'],
            'million' => [1_000_000, 'Satu juta Rupiah'],
            'billion' => [2_000_000_000, 'Dua miliar Rupiah'],
        ];
    }

    #[DataProvider('amountProvider')]
    public function test_spells_rupiah_amounts(int $amount, string $expected): void
    {
        $this->assertSame($expected, Terbilang::make($amount));
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Terbilang tidak mendukung nilai negatif.');

        Terbilang::make(-1);
    }
}
