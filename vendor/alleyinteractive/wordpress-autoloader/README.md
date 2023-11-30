# WordPress Autoloader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alleyinteractive/wordpress-autoloader.svg?style=flat-square)](https://packagist.org/packages/alleyinteractive/wordpress-autoloader)
[![Tests](https://github.com/alleyinteractive/wordpress-autoloader/actions/workflows/tests.yml/badge.svg)](https://github.com/alleyinteractive/wordpress-autoloader/actions/workflows/tests.yml)

A PHP Autoloader that supports the [Wordpress Coding
Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). For example, a folder that looks like this would be autoloaded as:

```
src/class-example-class.php -> Root_Namespace\Example_Class
src/trait-reusable-feature.php -> Root_Namesace\Reusable_Feature
src/feature/class-example-feature.php -> Root_Namespace\Feature\Example_Feature
```

Supports `class`, `trait`, `interface`, and `enum` files and any level of
namespaces.

## Installation

You can install the package via composer:

```bash
composer require alleyinteractive/wordpress-autoloader
```

## Usage

```php
Alley_Interactive\Autoloader\Autoloader::generate(
	'Plugin\\Namespace',
	__DIR__ . '/src',
)->register();

// Or register the autoloader manually.
spl_autoload_register(
	Alley_Interactive\Autoloader\Autoloader::generate(
		'Plugin\\Namespace',
		__DIR__ . '/src',
	)
);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
