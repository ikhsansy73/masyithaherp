<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Services\School\AcademicYearService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. One seeder per table:
     * funds → accounts (COA) → cash accounts → fee types → salary
     * components → permissions → roles → users, then the default academic
     * year (its model event auto-seeds terms and accounting periods).
     */
    public function run(): void
    {
        $this->call([
            FundSeeder::class,
            AccountSeeder::class,
            CashAccountSeeder::class,
            FeeTypeSeeder::class,
            SalaryComponentSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            SubjectSeeder::class,
        ]);

        if (AcademicYear::query()->doesntExist()) {
            app(AcademicYearService::class)->create('2026/2027', isDefault: true);
        }
    }
}
