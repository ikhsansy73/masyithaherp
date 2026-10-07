<?php

namespace Database\Seeders;

use App\Support\PermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/** Seeds the `permissions` table from the doc 09 matrix. */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (collect(PermissionMatrix::build())->flatten()->unique()->values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
