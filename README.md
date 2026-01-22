# Digitalsteps CMS

A Laravel package that transforms a Laravel Inertia React app into a full WordPress-like CMS.

## Features

- Plugin system with install, enable, disable, hooks, and filters
- Theme system using React layouts
- React-based admin dashboard
- Page builder with extensible blocks
- Role & permission system
- Slug-based routing and SEO support
- Media library and revisions
- Artisan commands for installation, plugin creation, and theme creation

## Installation

1. Install the package via Composer:
   ```bash
   composer require digitalsteps/cms
   ```

2. Run the installation command:
   ```bash
   php artisan cms:install
   ```

3. Publish the assets:
   ```bash
   php artisan vendor:publish --provider="Digitalsteps\Cms\Providers\CmsServiceProvider" --tag="cms-config"
   ```

## Usage

### Creating a Plugin

```bash
php artisan cms:create-plugin MyPlugin
```

### Creating a Theme

```bash
php artisan cms:create-theme MyTheme
```

### Admin Dashboard

Access the admin dashboard at `/admin` (configurable).

## Composer plugin: automatic Fortify handling

This package includes a Composer plugin that helps avoid conflicts with `laravel/fortify` when using the CMS as the primary auth provider.

- On `buni/cms` install: the plugin will remove `laravel/fortify` from the project's root `composer.json` (if present) and save a backup to `vendor/buni/cms/.fortify-backup.json`.
- On `buni/cms` uninstall: the plugin will restore the `laravel/fortify` entry back into the appropriate section of the root `composer.json` from the backup.
- After modifying `composer.json` the plugin will attempt to run `composer update --no-interaction` in the project root. If the automatic update fails the plugin prints the output and instructs you to run `composer update` manually.

Backup file: `vendor/buni/cms/.fortify-backup.json`.

Important:

- The plugin edits the root `composer.json` file but does not (and should not) remove or change any other project files. If you use continuous integration or have custom deploy scripts, ensure they account for this behavior.
- The CMS uses theme-driven rendering (see Themes section) and does not rely on `resources/views` for admin UI. The plugin only manages the `laravel/fortify` dependency to prevent runtime/auth conflicts when themes or the package provide authentication flows.

If you prefer not to use the automatic update step, run `composer require buni/cms --no-scripts` and manage the `composer.json` changes manually.

## License

MIT