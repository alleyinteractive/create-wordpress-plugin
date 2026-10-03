<?php
/**
 * Orchestrates Composer dependency scoping.
 *
 * 1. Runs php-scoper to prefix runtime dependencies into vendor-prefixed/.
 * 2. Regenerates a classmap autoloader over the scoped files + the plugin's
 *    own src/ (classmaps handle PSR-4 and WordPress-autoloader packages
 *    alike), and re-declares each runtime package's `autoload.files` (helper
 *    functions) so they continue to load eagerly under the new prefix.
 *
 * No-ops cleanly when php-scoper is not installed (e.g. a `--no-dev` install),
 * so it is safe to wire into Composer's post-install / post-update hooks.
 *
 * @package create-wordpress-plugin
 *
 * phpcs:disable
 */

declare(strict_types=1);

$root     = dirname( __DIR__ );
$scoper   = $root . '/vendor/bin/php-scoper';
$composer = getenv( 'COMPOSER_BINARY' ) ?: 'composer';

if ( ! is_file( $scoper ) ) {
	fwrite( STDOUT, "php-scoper is not installed; skipping dependency scoping.\n" );
	exit( 0 );
}

passthru(
	escapeshellarg( $scoper ) . ' add-prefix'
	. ' --config=' . escapeshellarg( $root . '/.scoper/scoper.inc.php' )
	. ' --force --quiet',
	$code
);

if ( 0 !== $code ) {
	exit( $code );
}

// Carry each runtime package's autoload.files forward so scoped helper
// functions (e.g. Mantle support helpers) keep loading eagerly.
$lock  = json_decode( (string) file_get_contents( $root . '/composer.lock' ), true );
$files = [];

foreach ( $lock['packages'] ?? [] as $package ) {
	$name = (string) ( $package['name'] ?? '' );

	foreach ( (array) ( $package['autoload']['files'] ?? [] ) as $file ) {
		$relative = $name . '/' . ltrim( (string) $file, '/' );

		if ( is_file( $root . '/vendor-prefixed/' . $relative ) ) {
			$files[] = './' . $relative;
		}
	}
}

file_put_contents(
	$root . '/vendor-prefixed/composer.json',
	json_encode(
		[
			'name'     => 'scoped/dependencies',
			'version'  => '1.0.0',
			'autoload' => [
				'classmap'              => [ '.', '../src' ],
				'files'                 => array_values( array_unique( $files ) ),
				'exclude-from-classmap' => [ '/nesbot/carbon/lazy/' ],
			],
			'config'   => [ 'classmap-authoritative' => true ],
		],
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	) . "\n"
);

passthru(
	escapeshellarg( $composer ) . ' dump-autoload'
	. ' --working-dir=' . escapeshellarg( $root . '/vendor-prefixed' )
	. ' --classmap-authoritative --no-interaction',
	$code
);

exit( $code );
