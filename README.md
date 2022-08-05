<!--delete-->
# Create WordPress Plugin

This is a skeleton WordPress plugin that can scaffold a WordPress plugin. This
template includes a base plugin file, autoloaded PHP files, unit tests powered
by [Mantle](https://mantle.alley.co/), front-end assets compiled via Webpack,
and Continuous Integration [via GitHub Actions](.github/workflows). Actions are
configured to test the plugin and also build it for release. A `main-built`
branch and `v0.1.0-built` tags will be created with all dependencies included
for use when submoduling a plugin. The built branches will include Composer
dependencies and any compiled front-end assets (if using them).

The plugin supports front-end assets which can be enqueued inside
`src/assets.php`. For plugins that don't require front-end assets, the
configuration script below will prompt you to delete the front-end files if you
don't wish to use them.

## Getting Started

Follow these steps to get started:

1. Press the "Use template" button at the top of this repo to create a new repo
   with the contents of this skeleton.
2. Run `make` (or `php ./configure.php`) to run a script that will replace all
   placeholders throughout all the files.
3. Have fun creating your plugin! 🎊

<!--/delete-->

# create-wordpress-plugin

Stable tag: 0.1.0

Requires at least: 5.9

Tested up to: 5.9

Requires PHP: 7.4

License: GPL v2 or later

Tags: vendor_name, create-wordpress-plugin

Contributors: author_username

[![Coding Standards](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/unit-test.yml)

plugin_description

## Installation

You can install the package via composer:

```bash
composer require alleyinteractive/create-wordpress-plugin
```

## Usage

Activate the plugin in WordPress and use it like so:

```php
$plugin = Vendor_Name\Skeleton\Example_Plugin();
$plugin->perform_magic();
```

## Testing

Run `npm run test` to run Jest tests against JavaScript files. Run
`npm run test:watch` to keep the test runner open and watching for changes.

Run `npm run lint` to run ESLint against all JavaScript files. Linting will also
happen when running development or production builds.

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.

## Updating Dependencies

To update `@wordpress` dependencies, simply execute:

```sh
npm run update-dependencies WPVERSION
```

Where `WPVERSION` is the version of WordPress you are targeting. The version
must include both the major and patch version (e.g., `5.9.3`). For example:

```sh
npm run update-dependencies 5.9.3
```

The versions are drawn from tags on
[wordpress-develop](https://github.com/WordPress/wordpress-develop/tags).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.co/careers/).

- [author_name](https://github.com/author_name)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
