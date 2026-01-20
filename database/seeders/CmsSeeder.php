<?php

namespace Buni\Cms\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Buni\Cms\Models\Setting;

class CmsSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        Permission::create(['name' => 'manage pages']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage plugins']);
        Permission::create(['name' => 'manage themes']);
        Permission::create(['name' => 'manage settings']);

        // Create roles
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(['manage pages', 'manage users', 'manage plugins', 'manage themes', 'manage settings']);

        $editor = Role::create(['name' => 'editor']);
        $editor->givePermissionTo(['manage pages']);

        // Seed default settings
        Setting::set('site_name', 'My CMS Site', 'string', 'site');
        Setting::set('site_description', '', 'string', 'site');
        Setting::set('timezone', 'UTC', 'string', 'site');
        Setting::set('language', 'en', 'string', 'site');

        Setting::set('mail_driver', 'smtp', 'string', 'mail');
        Setting::set('mail_host', '', 'string', 'mail');
        Setting::set('mail_port', '587', 'string', 'mail');
        Setting::set('mail_username', '', 'string', 'mail');
        Setting::set('mail_password', '', 'string', 'mail');
        Setting::set('mail_encryption', 'tls', 'string', 'mail');
        Setting::set('mail_from_address', '', 'string', 'mail');
        Setting::set('mail_from_name', '', 'string', 'mail');

        Setting::set('db_host', env('DB_HOST', '127.0.0.1'), 'string', 'database');
        Setting::set('db_port', env('DB_PORT', '3306'), 'string', 'database');
        Setting::set('db_database', env('DB_DATABASE', 'laravel'), 'string', 'database');
        Setting::set('db_username', env('DB_USERNAME', 'root'), 'string', 'database');
        Setting::set('db_password', '', 'string', 'database');
    }
}