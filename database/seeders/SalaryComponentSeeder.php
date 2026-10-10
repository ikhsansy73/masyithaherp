<?php

namespace Database\Seeders;

use App\Enums\SalaryCalculation;
use App\Enums\SalaryComponentType;
use App\Models\Account;
use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

/**
 * Master komponen gaji from doc 05 §2. Percentages are data (percent_rate),
 * so regulatory changes are seeder/row edits, not code.
 */
class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $gl = fn (string $code): ?int => Account::query()->where('code', $code)->value('id');

        $rows = [
            ['code' => 'GAJI_POKOK', 'name' => 'Gaji Pokok', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::Fixed, 'gl_account_id' => $gl('5-1110')],
            ['code' => 'TUNJ_JABATAN', 'name' => 'Tunjangan Jabatan', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::Fixed, 'gl_account_id' => $gl('5-1120')],
            ['code' => 'TUNJ_TRANSPORT', 'name' => 'Tunjangan Transport', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::Fixed, 'gl_account_id' => $gl('5-1120')],
            ['code' => 'HONOR_PER_JAM', 'name' => 'Honor per JP', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::ManualEntry, 'gl_account_id' => $gl('5-1130')],
            ['code' => 'BONUS_THR', 'name' => 'Bonus / THR', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::ManualEntry, 'gl_account_id' => $gl('5-1150')],
            ['code' => 'BPJS_KES_PEG', 'name' => 'BPJS Kesehatan (Pegawai)', 'type' => SalaryComponentType::Potongan, 'calculation' => SalaryCalculation::PercentBase, 'percent_rate' => '0.0100', 'liability_account_id' => $gl('2-1200')],
            ['code' => 'BPJS_TK_JHT_PEG', 'name' => 'BPJS TK JHT (Pegawai)', 'type' => SalaryComponentType::Potongan, 'calculation' => SalaryCalculation::PercentBase, 'percent_rate' => '0.0200', 'liability_account_id' => $gl('2-1200')],
            ['code' => 'BPJS_KES_PSH', 'name' => 'BPJS Kesehatan (Sekolah)', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::PercentBase, 'percent_rate' => '0.0400', 'is_employer' => true, 'gl_account_id' => $gl('5-1140'), 'liability_account_id' => $gl('2-1200')],
            ['code' => 'BPJS_TK_PSH', 'name' => 'BPJS TK (Sekolah)', 'type' => SalaryComponentType::Pendapatan, 'calculation' => SalaryCalculation::PercentBase, 'percent_rate' => '0.0624', 'is_employer' => true, 'gl_account_id' => $gl('5-1140'), 'liability_account_id' => $gl('2-1200')],
            ['code' => 'PPH21', 'name' => 'PPh 21', 'type' => SalaryComponentType::Potongan, 'calculation' => SalaryCalculation::ManualEntry, 'liability_account_id' => $gl('2-1300')],
            ['code' => 'POTONGAN_LAIN', 'name' => 'Potongan Lain-lain', 'type' => SalaryComponentType::Potongan, 'calculation' => SalaryCalculation::Fixed, 'liability_account_id' => $gl('2-1100')],
        ];

        foreach ($rows as $row) {
            SalaryComponent::query()->updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
