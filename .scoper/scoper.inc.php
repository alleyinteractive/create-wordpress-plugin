<?php
/**
 * php-scoper configuration.
 *
 * Namespace-agnostic: the prefix is derived from the plugin's own root
 * namespace (declared in composer.json under
 * extra.wordpress-autoloader.autoload) with a `\Dependencies` suffix, so this
 * file works unchanged for any plugin scaffolded from create-wordpress-plugin.
 *
 * Only runtime dependencies (composer.lock "packages") are scoped; development
 * tools such as php-scoper, PHPUnit and Rector are never prefixed.
 *
 * @package create-wordpress-plugin
 *
 * phpcs:disable
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$base     = __DIR__ . '/..';
$composer = json_decode( (string) file_get_contents( $base . '/composer.json' ), true );

$root_namespace = rtrim(
	(string) array_key_first( $composer['extra']['wordpress-autoloader']['autoload'] ?? [ '' => '' ] ),
	'\\'
);

if ( '' === $root_namespace ) {
	fwrite( STDERR, "Unable to derive the root namespace from composer.json.\n" );
	exit( 1 );
}

$prefix = $root_namespace . '\\Dependencies';

// Build the finder from runtime packages only (composer.lock "packages"), so
// development tooling is never scoped.
$lock    = json_decode( (string) file_get_contents( $base . '/composer.lock' ), true );
$runtime = array_values(
	array_filter(
		array_map(
			static fn ( array $package ): string => (string) ( $package['name'] ?? '' ),
			$lock['packages'] ?? []
		)
	)
);

$finder = Finder::create()
	->files()
	->ignoreVCS( true )
	->name( '*.php' )
	->in( $base . '/vendor' );

foreach ( $runtime as $name ) {
	$finder->path( '#^' . preg_quote( $name, '#' ) . '/#' );
}

// WordPress core symbols must never be prefixed.
$wp = static function ( string $file ) use ( $base ): array {
	$path = $base . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $file;

	return is_file( $path ) ? (array) json_decode( (string) file_get_contents( $path ), true ) : [];
};

return [
	'prefix'             => $prefix,
	'output-dir'         => $base . '/vendor-prefixed',
	'finders'            => [ $finder ],
	// Never prefix the plugin's own classes (AC #2).
	'exclude-namespaces' => [ $root_namespace ],
	'exclude-classes'    => array_merge(
		$wp( 'exclude-wordpress-classes.json' ),
		$wp( 'exclude-wordpress-interfaces.json' )
	),
	'exclude-functions'  => $wp( 'exclude-wordpress-functions.json' ),
	'exclude-constants'  => $wp( 'exclude-wordpress-constants.json' ),
];
