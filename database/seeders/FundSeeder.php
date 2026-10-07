<?php

namespace Database\Seeders;

use App\Enums\FundType;
use App\Models\Fund;
use Illuminate\Database\Seeder;

class FundSeeder extends Seeder
{
    public function run(): void
    {
        $funds = [
            ['code' => 'BOS', 'name' => 'Dana BOS', 'type' => FundType::Pemerintah, 'description' => 'Dana Bantuan Operasional Sekolah dari pemerintah'],
            ['code' => 'YYS', 'name' => 'Yayasan', 'type' => FundType::Yayasan, 'description' => 'Dana operasional yayasan'],
            ['code' => 'KOM', 'name' => 'Komite', 'type' => FundType::Komite, 'description' => 'Dana komite/komunitas (SPP, DSP, kegiatan siswa)'],
            ['code' => 'UMUM', 'name' => 'Umum/Konsolidasi', 'type' => FundType::Lainnya, 'description' => 'Dana umum tanpa alokasi khusus'],
        ];

        foreach ($funds as $fund) {
            Fund::query()->firstOrCreate(
                ['code' => $fund['code']],
                [...$fund, 'is_active' => true],
            );
        }
    }
}
