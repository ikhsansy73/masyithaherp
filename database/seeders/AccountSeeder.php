<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\CashFlowCategory;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\Fund;
use Illuminate\Database\Seeder;

/**
 * Chart of accounts seed from docs/design/03-accounting-core.md §2.
 * All seeded rows are locked (is_locked = true): other ranges are
 * system-owned; bendahara/super_admin may only add new 5-xxxx accounts.
 */
class AccountSeeder extends Seeder
{
    /**
     * @param  array{code: string, name: string, type: AccountType, normal_balance: NormalBalance, is_header?: bool, parent?: string, cash_flow?: ?CashFlowCategory, default_fund?: ?string}  $row
     */
    private function upsert(array $row): Account
    {
        return Account::query()->firstOrCreate(
            ['code' => $row['code']],
            [
                'name' => $row['name'],
                'type' => $row['type'],
                'normal_balance' => $row['normal_balance'],
                'is_header' => $row['is_header'] ?? false,
                'parent_id' => ($row['parent'] ?? null) !== null
                    ? Account::query()->where('code', $row['parent'])->value('id')
                    : null,
                'cash_flow_category' => $row['cash_flow'] ?? null,
                'default_fund_id' => ($row['default_fund'] ?? null) !== null
                    ? Fund::query()->where('code', $row['default_fund'])->value('id')
                    : null,
                'is_locked' => true,
                'is_active' => true,
            ],
        );
    }

    public function run(): void
    {
        // Order matters: parents must exist before children reference them.
        foreach ($this->chart() as $row) {
            $this->upsert($row);
        }
    }

    /**
     * @return list<array{code: string, name: string, type: AccountType, normal_balance: NormalBalance, is_header?: bool, parent?: ?string, cash_flow?: ?CashFlowCategory, default_fund?: ?string}>
     */
    private function chart(): array
    {
        return [
            // --- 1-xxxx Aset ---
            ['code' => '1-0000', 'name' => 'ASET', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'is_header' => true, 'parent' => null],
            ['code' => '1-1000', 'name' => 'Aset Lancar', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'is_header' => true, 'parent' => '1-0000'],
            ['code' => '1-1100', 'name' => 'Kas', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-1000', 'cash_flow' => CashFlowCategory::NonKas, 'default_fund' => 'UMUM'],
            ['code' => '1-1150', 'name' => 'Kas Kecil', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-1000', 'cash_flow' => CashFlowCategory::NonKas, 'default_fund' => 'UMUM'],
            ['code' => '1-1200', 'name' => 'Bank', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-1000', 'cash_flow' => CashFlowCategory::NonKas],
            ['code' => '1-1300', 'name' => 'Piutang Siswa', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-1000', 'cash_flow' => CashFlowCategory::NonKas],
            ['code' => '1-1400', 'name' => 'Perlengkapan (Persediaan ATK)', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-1000', 'cash_flow' => CashFlowCategory::NonKas, 'default_fund' => 'UMUM'],
            ['code' => '1-1500', 'name' => 'Biaya Dibayar di Muka', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-1000', 'cash_flow' => CashFlowCategory::NonKas, 'default_fund' => 'UMUM'],
            ['code' => '1-2000', 'name' => 'Aset Tetap', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'is_header' => true, 'parent' => '1-0000'],
            ['code' => '1-2100', 'name' => 'Tanah', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-2000', 'cash_flow' => CashFlowCategory::Investasi],
            ['code' => '1-2200', 'name' => 'Gedung & Bangunan', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-2000', 'cash_flow' => CashFlowCategory::Investasi],
            ['code' => '1-2300', 'name' => 'Peralatan & Mesin', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-2000', 'cash_flow' => CashFlowCategory::Investasi],
            ['code' => '1-2400', 'name' => 'Meubelair (Perabot)', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-2000', 'cash_flow' => CashFlowCategory::Investasi],
            ['code' => '1-2500', 'name' => 'Buku Perpustakaan', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Debit, 'parent' => '1-2000', 'cash_flow' => CashFlowCategory::Investasi],
            ['code' => '1-2900', 'name' => 'Akumulasi Penyusutan', 'type' => AccountType::Aset, 'normal_balance' => NormalBalance::Kredit, 'parent' => '1-2000', 'cash_flow' => CashFlowCategory::NonKas],

            // --- 2-xxxx Kewajiban ---
            ['code' => '2-0000', 'name' => 'KEWAJIBAN', 'type' => AccountType::Kewajiban, 'normal_balance' => NormalBalance::Kredit, 'is_header' => true, 'parent' => null],
            ['code' => '2-1100', 'name' => 'Utang Gaji', 'type' => AccountType::Kewajiban, 'normal_balance' => NormalBalance::Kredit, 'parent' => '2-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '2-1200', 'name' => 'Utang BPJS', 'type' => AccountType::Kewajiban, 'normal_balance' => NormalBalance::Kredit, 'parent' => '2-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '2-1300', 'name' => 'Utang PPh 21', 'type' => AccountType::Kewajiban, 'normal_balance' => NormalBalance::Kredit, 'parent' => '2-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '2-1400', 'name' => 'Utang Usaha / Vendor', 'type' => AccountType::Kewajiban, 'normal_balance' => NormalBalance::Kredit, 'parent' => '2-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '2-1500', 'name' => 'Pendapatan Diterima di Muka', 'type' => AccountType::Kewajiban, 'normal_balance' => NormalBalance::Kredit, 'parent' => '2-0000', 'cash_flow' => CashFlowCategory::Operasi],

            // --- 3-xxxx Ekuitas / Dana ---
            ['code' => '3-0000', 'name' => 'EKUITAS / DANA', 'type' => AccountType::Ekuitas, 'normal_balance' => NormalBalance::Kredit, 'is_header' => true, 'parent' => null],
            ['code' => '3-1100', 'name' => 'Saldo Dana Awal (Ekuitas)', 'type' => AccountType::Ekuitas, 'normal_balance' => NormalBalance::Kredit, 'parent' => '3-0000', 'cash_flow' => CashFlowCategory::NonKas],
            ['code' => '3-1200', 'name' => 'Surplus / Defisit Tahun Berjalan', 'type' => AccountType::Ekuitas, 'normal_balance' => NormalBalance::Kredit, 'parent' => '3-0000', 'cash_flow' => CashFlowCategory::NonKas],

            // --- 4-xxxx Pendapatan ---
            ['code' => '4-0000', 'name' => 'PENDAPATAN', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'is_header' => true, 'parent' => null],
            ['code' => '4-1100', 'name' => 'Pendapatan SPP', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'KOM'],
            ['code' => '4-1200', 'name' => 'Pendapatan DSP', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'KOM'],
            ['code' => '4-1300', 'name' => 'Pendapatan Dana BOS', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'BOS'],
            ['code' => '4-1400', 'name' => 'Pendapatan Seragam', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'KOM'],
            ['code' => '4-1500', 'name' => 'Pendapatan Buku & LKS', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'KOM'],
            ['code' => '4-1600', 'name' => 'Pendapatan Kegiatan', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'KOM'],
            ['code' => '4-1700', 'name' => 'Pendapatan Donasi / Infaq', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],
            ['code' => '4-1900', 'name' => 'Pendapatan Lain-lain', 'type' => AccountType::Pendapatan, 'normal_balance' => NormalBalance::Kredit, 'parent' => '4-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],

            // --- 5-xxxx Beban ---
            ['code' => '5-0000', 'name' => 'BEBAN', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'is_header' => true, 'parent' => null],
            ['code' => '5-1110', 'name' => 'Beban Gaji Pokok', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],
            ['code' => '5-1120', 'name' => 'Beban Tunjangan', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],
            ['code' => '5-1130', 'name' => 'Beban Honor', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],
            ['code' => '5-1140', 'name' => 'Beban BPJS (bagian sekolah)', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],
            ['code' => '5-1150', 'name' => 'Beban Bonus / THR', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'YYS'],
            ['code' => '5-1300', 'name' => 'Beban Penyusutan', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1400', 'name' => 'Beban Perlengkapan & ATK', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1500', 'name' => 'Beban Beasiswa & Potongan', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi, 'default_fund' => 'KOM'],
            ['code' => '5-1600', 'name' => 'Beban Kegiatan Siswa', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1710', 'name' => 'Beban Listrik, Air & Telepon', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1720', 'name' => 'Beban Internet & Langganan', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1730', 'name' => 'Beban Kebersihan & Keamanan', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1800', 'name' => 'Beban Perawatan & Perbaikan', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1900', 'name' => 'Beban Administrasi & Lain-lain', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Operasi],
            ['code' => '5-1950', 'name' => 'Beban/Selisih Penghapusan Aset', 'type' => AccountType::Beban, 'normal_balance' => NormalBalance::Debit, 'parent' => '5-0000', 'cash_flow' => CashFlowCategory::Investasi],
        ];
    }
}
