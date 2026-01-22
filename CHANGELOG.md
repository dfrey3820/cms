# Changelog

All notable changes to this project will be documented in this file.

## [v1.3.4] - 2026-01-22

- composer-plugin: add automatic Fortify handling
  - Adds a Composer plugin that removes `laravel/fortify` from the root project's `composer.json` when `buni/cms` is installed, and restores it on uninstall.
  - The plugin saves a backup to `vendor/buni/cms/.fortify-backup.json`.
  - After modifying `composer.json` the plugin attempts to run `composer update --no-interaction` in the project root; if the automatic update fails a message is printed and manual `composer update` is required.
- docs: README updated to document the plugin behavior and backup location.

## [v1.3.3]
- Previous release notes omitted for brevity.
