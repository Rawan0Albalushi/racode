<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('racode.permissions') as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $super = Role::findOrCreate('super-admin', 'web');
        $super->syncPermissions(Permission::all());

        foreach (config('racode.roles') as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }
    }
}
