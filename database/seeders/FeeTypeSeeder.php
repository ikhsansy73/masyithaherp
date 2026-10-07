<?php

namespace Database\Seeders;

use App\Enums\FeeCategory;
use App\Models\Account;
use App\Models\FeeType;
use Illuminate\Database\Seeder;

/**
 * The six seeded fee types from doc 04 §1, each mapped to its revenue
 * account in the COA (AccountSeeder runs first).
 */
class FeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $feeTypes = [
            ['code' => 'SPP', 'name' => 'SPP (Sumbangan Pembinaan Pendidikan)', 'category' => FeeCategory::Bulanan, 'revenue_account' => '4-1100'],
            ['code' => 'DSP', 'name' => 'DSP (Sumbangan Pengembangan Pendidikan)', 'category' => FeeCategory::Tahunan, 'revenue_account' => '4-1200'],
            ['code' => 'SERAGAM', 'name' => 'Seragam', 'category' => FeeCategory::Insidental, 'revenue_account' => '4-1400'],
            ['code' => 'BUKU', 'name' => 'Buku & LKS', 'category' => FeeCategory::Tahunan, 'revenue_account' => '4-1500'],
            ['code' => 'KEGIATAN', 'name' => 'Kegiatan', 'category' => FeeCategory::Tahunan, 'revenue_account' => '4-1600'],
            ['code' => 'WISUDA', 'name' => 'Wisuda', 'category' => FeeCategory::Insidental, 'revenue_account' => '4-1600'],
        ];

        foreach ($feeTypes as $feeType) {
            FeeType::query()->firstOrCreate(
                ['code' => $feeType['code']],
                [
                    ...$feeType,
                    'revenue_account_id' => Account::query()->where('code', $feeType['revenue_account'])->value('id'),
                    'is_active' => true,
                ],
            );
        }
    }
}
