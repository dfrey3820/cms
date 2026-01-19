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

## License

MIT