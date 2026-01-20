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

        // Create super-admin role
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(['manage pages', 'manage users', 'manage plugins', 'manage themes', 'manage settings']);

        // Seed default settings using session data if available, otherwise use defaults
        Setting::set('site_name', session('install_site_name', 'My CMS Site'), 'string', 'site');
        Setting::set('site_description', '', 'string', 'site');
        Setting::set('timezone', session('install_timezone', 'UTC'), 'string', 'site');
        Setting::set('language', 'en', 'string', 'site');

        Setting::set('mail_driver', session('install_mail_driver', 'smtp'), 'string', 'mail');
        Setting::set('mail_host', session('install_mail_host', ''), 'string', 'mail');
        Setting::set('mail_port', session('install_mail_port', '587'), 'string', 'mail');
        Setting::set('mail_username', session('install_mail_username', ''), 'string', 'mail');
        Setting::set('mail_password', session('install_mail_password', ''), 'string', 'mail');
        Setting::set('mail_encryption', session('install_mail_encryption', 'tls'), 'string', 'mail');
        Setting::set('mail_from_address', '', 'string', 'mail');
        Setting::set('mail_from_name', session('install_site_name', 'My CMS Site'), 'string', 'mail');

        Setting::set('db_connection', session('install_db_connection', 'mysql'), 'string', 'database');
        Setting::set('db_host', session('install_db_host', '127.0.0.1'), 'string', 'database');
        Setting::set('db_port', session('install_db_port', '3306'), 'string', 'database');
        Setting::set('db_database', session('install_db_database', 'laravel'), 'string', 'database');
        Setting::set('db_username', session('install_db_username', 'root'), 'string', 'database');
        Setting::set('db_password', session('install_db_password', ''), 'string', 'database');

        // System settings
        Setting::set('debug_mode', 'false', 'boolean', 'system');
        Setting::set('maintenance_mode', 'false', 'boolean', 'system');
        Setting::set('cache_enabled', 'true', 'boolean', 'system');
        Setting::set('session_lifetime', '120', 'integer', 'system');

        // Server settings
        Setting::set('server_software', 'Laravel', 'string', 'server');
        Setting::set('php_version', PHP_VERSION, 'string', 'server');
        Setting::set('max_upload_size', ini_get('upload_max_filesize'), 'string', 'server');
        Setting::set('memory_limit', ini_get('memory_limit'), 'string', 'server');
    }
}