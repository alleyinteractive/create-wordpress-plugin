<?php
/**
 * PHP-Scoper configuration file.
 *
 * @package ai-services
 */

use Symfony\Component\Finder\Finder;

// Raise the memory limit.
ini_set( 'memory_limit', '512M' );

if ( !is_dir( __DIR__ . '/vendor' ) ) {
	throw new RuntimeException('Unable to find the vendor directory, have you executed composer install?');
}

$wp_classes   = json_decode( file_get_contents( __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-classes.json' ), true );
$wp_functions = json_decode( file_get_contents( __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-functions.json' ), true );
$wp_constants = json_decode( file_get_contents( __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-constants.json' ), true );

return [
	'prefix'             => 'Create_WordPress_Plugin_Vendor',
	'finders'            => [
		// Plugin files.
		// Finder::create()
		// 	->files()
		// 	->name( '*.php' )
		// 	->ignoreVCS( true )
		// 	->ignoreDotFiles( true )
		// 	->notName( '/LICENSE|.*\\.md|.*\\.json|.*\\.lock|.*\\.dist/' )
		// 	->in( [ __DIR__ . '/src', __DIR__ . '/blocks' ] ),
		// // Main plugin file.
		// Finder::create()->append( [ 'plugin.php' ] ),
		// Vendor dependencies.
		Finder::create()
			->files()
			->name( '*.php' )
			->ignoreVCS( true )
			->ignoreDotFiles( true )
			->notName( '/LICENSE|.*\\.md|.*\\.json|.*\\.lock|.*\\.dist/' )
			->exclude( [ 'docs', 'tests', 'node_modules', '.scoper' ] )
			// ->path( $wp_oop_plugin_lib_folders_regex )
			->in( __DIR__ . '/vendor' ),

		// Main composer.json file so that we can build a classmap.
		Finder::create()->append( [ 'composer.json' ] ),
	],
	'exclude-namespaces' => [
		// Composer/internal namespaces.
		'Composer\\',
		'Alley\\Autoloader\\',
		'Alley_Interactive\\Autoloader\\',
		'ComposerWordPressAutoloader\\',

		// Plugin namespace.
		'Create_WordPress_Plugin\\',
	],
	'exclude-classes'   => $wp_classes,
	'exclude-functions' => $wp_functions,
	'exclude-constants' => array_merge(
		$wp_constants,
		[
			'CREATE_WORDPRESS_PLUGIN_DIR',
		],
	),
	'expose-global-functions' => false,
];
