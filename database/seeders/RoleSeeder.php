<?php

namespace Database\Seeders;

use App\Support\PermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Seeds the `roles` table and syncs each role's permissions from the doc 09 matrix. */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionMatrix::build() as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
