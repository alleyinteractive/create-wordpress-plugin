<!--delete-->
# Create WordPress Plugin

This is a skeleton WordPress plugin that can scaffold a WordPress plugin. This
template includes a base plugin file, autoloaded PHP files, unit tests powered
by [Mantle](https://mantle.alley.com/), front-end assets compiled via Webpack,
and Continuous Integration [via GitHub Actions](.github/workflows). Actions are
configured to test the plugin and also build it for release. A `main-built`
branch and `v0.1.0-built` tags will be created with all dependencies included
for use when submoduling a plugin. The built branches will include Composer
dependencies and any compiled front-end assets (if using them).

The plugin supports front-end assets which can be enqueued inside
`src/assets.php` or from within an entry points `index.php` file. For plugins that don't require front-end assets, the configuration script below will prompt you to delete the front-end files if you don't wish to use them.

## Getting Started

Follow these steps to get started:

1. Press the "Use template" button at the top of this repo to create a new repo
   with the contents of this skeleton.
2. Run `make` (or `php ./configure.php`) to run a script that will replace all
   placeholders throughout all the files.
3. Have fun creating your plugin! 🎊

<!--/delete-->

# Create WordPress Plugin

Contributors: author_username

Tags: vendor_name, create-wordpress-plugin

Stable tag: 0.1.0

Requires at least: 5.9

Tested up to: 6.1

Requires PHP: 8.0

License: GPL v2 or later

[![Coding Standards](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/unit-test.yml)

A skeleton WordPress plugin.

## Installation

You can install the package via composer:

```bash
composer require alleyinteractive/create-wordpress-plugin
```

## Usage

Activate the plugin in WordPress and use it like so:

```php
$plugin = Create_WordPress_Plugin\Skeleton\Example_Plugin();
$plugin->perform_magic();
```
<!--front-end-->
## Testing

Run `npm run test` to run Jest tests against JavaScript files. Run
`npm run test:watch` to keep the test runner open and watching for changes.

Run `npm run lint` to run ESLint against all JavaScript files. Linting will also
happen when running development or production builds.

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.

### The `entries` directory and entry points

All directories created in the `entries` directory can serve as entry points and will be compiled with [@wordpress/scripts](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/README.md#scripts) into the `build` directory with an accompanied `index.asset.php` asset map.

#### Enqueuing Entry Points

You can also include an `index.php` file in the entry point directory for enqueueing or registering a script. This file will then be moved to the build directory and will be auto-loaded with the `load_scripts()` function in the `functions.php` file. Alternatively, if a script is to be enqueued elsewhere there are helper functions in the `src/assets.php` file for getting the assets.

### Scaffold a block with `create-block`

Use the `create-block` command to create custom blocks with [`@wordpress/create-block`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/) and follow the prompts to generate all the block assets in the `blocks/` directory.
Block registration, script creation, etc will be scaffolded from the `bin/create-block/templates/block/` templates. Run `npm run build` to compile and build the custom block. Blocks are enqueued using the `load_scripts()` function in `src/assets.php`.

### Updating WP Dependencies

Update the [WordPress dependency packages](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#packages-update) used in the project to their latest version.

To update `@wordpress` dependencies to their latest version use the packages-update command:

```sh
npx wp-scripts packages-update
```

This script provides the following custom options:

-   `--dist-tag` – allows specifying a custom dist-tag when updating npm packages. Defaults to `latest`. This is especially useful when using [`@wordpress/dependency-extraction-webpack-plugin`](https://www.npmjs.com/package/@wordpress/dependency-extraction-webpack-plugin). It lets installing the npm dependencies at versions used by the given WordPress major version for local testing, etc. Example:

```sh
npx wp-scripts packages-update --dist-tag=wp-WPVERSION`
```

Where `WPVERSION` is the version of WordPress you are targeting. The version
must include both the major and minor version (e.g., `6.1`). For example:

```sh
npx wp-scripts packages-update --dist-tag=wp-6.1`
```

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
