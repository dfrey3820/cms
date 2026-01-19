<?php

namespace Dsc\Cms\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CmsSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        Permission::create(['name' => 'manage pages']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage plugins']);
        Permission::create(['name' => 'manage themes']);

        // Create roles
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(['manage pages', 'manage users', 'manage plugins', 'manage themes']);

        $editor = Role::create(['name' => 'editor']);
        $editor->givePermissionTo(['manage pages']);
    }
}