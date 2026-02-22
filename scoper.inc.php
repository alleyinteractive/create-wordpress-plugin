<?php
/**
 * PHP-Scoper configuration for create-wordpress-plugin.
 *
 * This file configures php-scoper to prefix all vendor namespace dependencies
 * with the plugin's namespace to prevent conflicts with other plugins/themes.
 *
 * @see https://github.com/humbug/php-scoper
 *
 * phpcs:disable
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	/*
	 * The prefix to apply to all vendor namespaces.
	 *
	 * This value is automatically updated to match your plugin's namespace
	 * when you run configure.php during scaffolding.
	 */
	'prefix' => 'Alley\\WP\\Create_WordPress_Plugin\\Vendor',

	/*
	 * By default, when running php-scoper, it will prefix the namespace of all
	 * the files found. You can however define which finders should be used.
	 */
	'finders' => [
		Finder::create()
			->files()
			->ignoreVCS( true )
			->notName(
				[
					'*.md',
					'*.dist',
					'Makefile',
					'composer.json',
					'composer.lock',
				]
			)
			->exclude(
				[
					'doc',
					'test',
					'test_old',
					'tests',
					'Tests',
					'vendor-bin',
				]
			)
			->in( 'vendor' ),
		Finder::create()->append(
			[
				'composer.json',
			]
		),
	],

	/*
	 * Namespaces to exclude from prefixing. Any class using these namespaces
	 * will not be scoped.
	 */
	'exclude-namespaces' => [
		// WordPress core namespaces.
		'WP',
		'Automattic',
	],

	/*
	 * Expose global constants, classes, and functions.
	 */
	'expose-global-constants' => true,
	'expose-global-classes'   => true,
	'expose-global-functions' => true,

	/*
	 * Patchers are used to make changes to the scoped files that cannot be
	 * handled automatically.
	 */
	'patchers' => [],
];
