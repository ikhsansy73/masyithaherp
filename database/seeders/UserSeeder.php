<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo account per role (password: "password"). Local development only.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrator', 'email' => 'admin@masyithah.sch.id', 'roles' => ['super_admin']],
            ['name' => 'Hj. Siti Aminah, S.Pd., M.Pd.', 'email' => 'kepala@masyithah.sch.id', 'roles' => ['kepala_sekolah']],
            ['name' => 'Budi Santoso, S.E.', 'email' => 'bendahara@masyithah.sch.id', 'roles' => ['bendahara']],
            ['name' => 'Rina Wulandari', 'email' => 'tu@masyithah.sch.id', 'roles' => ['operator_tu']],
            ['name' => 'Ahmad Fauzi, S.Pd.', 'email' => 'guru@masyithah.sch.id', 'roles' => ['guru']],
            ['name' => 'Dewi Lestari, S.Pd.', 'email' => 'walikelas@masyithah.sch.id', 'roles' => ['guru', 'wali_kelas']],
            ['name' => 'Ibu Anisa (Wali Murid)', 'email' => 'wali@masyithah.sch.id', 'roles' => ['wali_murid']],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->syncRoles($userData['roles']);
        }
    }
}
