# Changelog

All notable changes to `alleyinteractive/composer-wordpress-autoloader` will be
documented in this file.

## Unreleased

## v0.8.0

- Automatically translate the `vendor-dir` and set the autoloaded files relative to the root directory of the project.

## v0.7.0

- Add APCu autoloader.
- Bump `alleyinteractive/wordpress-autoloader` to `v1.1.0`.

## v0.6.0

- Simplify injection of autoloader.
- Automatically load the autoloader inside of `vendor/autoload.php` without the
  need to load `vendor/wordpress-autoload.php` manually.

## v0.4.1

### Updated

* Fix Composer Injection to `vendor/autoload.php` in https://github.com/alleyinteractive/composer-wordpress-autoloader/pull/10

## v0.4.0

### Added

- Bump alleyinteractive/wordpress-autoloader to 0.2 by @srtfisher in https://github.com/alleyinteractive/composer-wordpress-autoloader/pull/7
- Supports PHP 8.1

## 0.3.0

- Remove specific Composer version dependency.

## 0.2.0

- Updates autoloader to use non-hard-coded paths.
- Adds support for dependencies to autoload files as well, fixes [#3](https://github.com/alleyinteractive/composer-wordpress-autoloader/issues/3).

## 0.1.0

- Initial release.
