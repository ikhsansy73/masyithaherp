<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

/**
 * Kelompok aset master (doc 02 §7): GL mapping 1-2100…1-2500 + default
 * masa manfaat. Tanah (dan buku perpustakaan) tidak disusutkan.
 */
class AssetCategorySeeder extends Seeder
{
    /**
     * @param  array{code: string, name: string, life: ?int, account: string, depreciable: bool}  $row
     */
    private function upsert(array $row): void
    {
        AssetCategory::query()->firstOrCreate(
            ['code' => $row['code']],
            [
                'name' => $row['name'],
                'useful_life_months' => $row['life'],
                'asset_account_id' => Account::query()->where('code', $row['account'])->value('id'),
                'is_depreciable' => $row['depreciable'],
            ],
        );
    }

    public function run(): void
    {
        foreach ($this->categories() as $row) {
            $this->upsert($row);
        }
    }

    /**
     * @return list<array{code: string, name: string, life: ?int, account: string, depreciable: bool}>
     */
    private function categories(): array
    {
        return [
            ['code' => 'GED', 'name' => 'Gedung & Bangunan', 'life' => 240, 'account' => '1-2200', 'depreciable' => true],
            ['code' => 'TNH', 'name' => 'Tanah', 'life' => null, 'account' => '1-2100', 'depreciable' => false],
            ['code' => 'PERL', 'name' => 'Peralatan & Mesin', 'life' => 48, 'account' => '1-2300', 'depreciable' => true],
            ['code' => 'ELK', 'name' => 'Elektronik', 'life' => 48, 'account' => '1-2300', 'depreciable' => true],
            ['code' => 'MBL', 'name' => 'Meubelair (Perabot)', 'life' => 60, 'account' => '1-2400', 'depreciable' => true],
            ['code' => 'BUKU', 'name' => 'Buku Perpustakaan', 'life' => null, 'account' => '1-2500', 'depreciable' => false],
        ];
    }
}
