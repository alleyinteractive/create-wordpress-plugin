#!/usr/bin/env php
<?php
/**
 * Configure the WordPress Plugin interactively.
 *
 * Supports arguments to set the values directly.
 *
 * [--author_name=<author_name>]
 * : The author name.
 *
 * [--author_email=<author_email>]
 * : The author email.
 *
 * phpcs:disable
 */

namespace Alley\WP\Create_WordPress_Plugin\Configure;

if ( ! defined( 'STDIN' ) ) {
	die( 'Not in CLI mode.' );
}

if ( 0 === strpos( strtoupper( PHP_OS ), 'WIN' ) ) {
	die( 'Not supported in Windows. 🪟' );
}

if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
	die( 'PHP 8.1.0 or greater is required.' );
}

// Parse the command line arguments from $argv.
$args         = [];
$previous_key = null;

foreach ( $argv as $value ) {
	if ( str_starts_with( $value, '--' ) ) {
		if ( false !== strpos( $value, '=' ) ) {
			[ $arg, $value ] = explode( '=', substr( $value, 2 ), 2 );

			$args[ $arg ] = trim( $value );

			$previous_key = null;
		} else {
			$args[ substr( $value, 2 ) ] = true;

			$previous_key = substr( $value, 2 );
		}
	} elseif ( ! empty( $previous_key ) ) {
		$args[ $previous_key ] = trim( $value );
	} else {
		$previous_key = trim( $value );
	}
}

$terminal_width = (int) exec( 'tput cols' );

function write( string $text ): void {
	global $terminal_width;
	echo wordwrap( $text, $terminal_width - 1 ) . PHP_EOL;
}

function ask( string $question, string $default = '', bool $allow_empty = true ): string {
	while ( true ) {
		write( $question . ( $default ? " [{$default}]" : '' ) . ': ' );
		$answer = readline( '> ' );

		$value = $answer ?: $default;

		if ( ! $allow_empty && empty( $value ) ) {
			echo "This value can't be empty." . PHP_EOL;
			continue;
		}

		return $value;
	}
}

function confirm( string $question, bool $default = false ): bool {
	write( "{$question} (yes/no) [" . ( $default ? 'yes' : 'no' ) . ']: ' );

	$answer = readline( '> ' );

	if ( ! $answer ) {
		return $default;
	}

	return in_array( strtolower( trim( $answer ) ), [ 'y', 'yes', 'true', '1' ], true );
}

function run( string $command, string $dir = null ): string {
	$command = $dir ? "cd {$dir} && {$command}" : $command;

	return trim( (string) shell_exec( $command ) );
}

function str_after( string $subject, string $search ): string {
	$pos = strrpos( $subject, $search );

	if ( $pos === false ) {
		return $subject;
	}

	return substr( $subject, $pos + strlen( $search ) );
}

function slugify( string $subject ): string {
	return strtolower( trim( (string) preg_replace( '/[^A-Za-z0-9-]+/', '-', $subject ), '-' ) );
}

function title_case( string $subject ): string {
	return ensure_capitalp( str_replace( ' ', '_', ucwords( str_replace( [ '-', '_' ], ' ', $subject ) ) ) );
}

function ensure_capitalp( string $text ): string {
	return str_replace( 'Wordpress', 'WordPress', $text );
}

/**
 * @param string $file
 * @param array<string, string> $replacements
 */
function replace_in_file( string $file, array $replacements ): void {
	$contents = file_get_contents( $file );

	if ( empty( $contents ) ) {
		return;
	}

	file_put_contents(
		$file,
		str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$contents,
		)
	);
}

function remove_readme_paragraphs( string $file ): void {
	$contents = file_get_contents( $file );

	if ( empty( $contents ) ) {
		return;
	}

	file_put_contents(
		$file,
		trim( (string) preg_replace( '/<!--delete-->.*<!--\/delete-->/s', '', $contents ) ?: $contents ),
	);
}

function remove_composer_require(): void {
	global $plugin_file;

	$contents = file_get_contents( $plugin_file );

	if ( empty( $contents ) ) {
		return;
	}

	file_put_contents(
		$plugin_file,
		trim( (string) preg_replace( '/\n\/\* Start Composer Loader \*\/.*\/\* End Composer Loader \*\/\n/s', '', $contents ) ?: $contents ) . PHP_EOL,
	);

	echo "Removed Composer's vendor/autoload.php from {$plugin_file}" . PHP_EOL;
}

function remove_composer_wrapper_comments(): void {
	global $plugin_file;

	$contents = file_get_contents( $plugin_file );

	if ( empty( $contents ) ) {
		return;
	}

	file_put_contents(
		$plugin_file,
		trim( preg_replace( '/\n\/\* (Start|End) Composer Loader \*\/\n/', '', $contents ) ?: $contents ) . PHP_EOL,
	);

	echo "Removed Composer's wrapper comments from {$plugin_file}" . PHP_EOL;
}

function remove_composer_files(): void {
	$file_list = [
		'composer.json',
		'composer.lock',
		'vendor/',
	];

	delete_files( $file_list );

	write( sprintf( 'Removed %s files.', implode( ', ', $file_list ) ) );
}

function remove_project_files(): void {
	$file_list = [
		'CHANGELOG.md',
		'.deployignore',
		'.editorconfig',
		'.gitignore',
		'.gitattributes',
		'.github',
		'LICENSE',
	];

	delete_files( $file_list );

	write( sprintf( 'Removed %s files.', implode( ', ', $file_list ) ) );
}

function rollup_phpcs_to_parent( string $parent_file, string $local_file, string $plugin_name, string $plugin_domain ): void {
	$config = '<?xml version="1.0"?>
<ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="' . $plugin_name . ' Configuration" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/squizlabs/PHP_CodeSniffer/master/phpcs.xsd">
  <description>PHP_CodeSniffer standard for ' . $plugin_name . '</description>

  <!-- DO NOT ADD ADDITIONAL RULES TO THIS FILE. Modifications belong in the root-level configuration. -->

  <!-- Include Root Rules -->
  <rule ref="' . $parent_file . '" />

  <rule ref="WordPress.WP.I18n">
    <properties>
      <!--
      Verify that the text_domain is set to the desired text-domain.
      Multiple valid text domains can be provided as a comma-delimited list.
       -->
      <property name="text_domain" type="array" value="' . $plugin_domain . '" />
    </properties>
  </rule>

  <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
    <properties>
      <property name="prefixes" type="array" value="' . str_replace( '-', '_', $plugin_domain ) . '" />
    </properties>
  </rule>
</ruleset>';

	if ( file_put_contents( $local_file, $config ) ) {
		delete_files( '.phpcs' );

		echo "Updated {$local_file}.\n";
	}
}

/* Remove the README's assets section. */
function remove_assets_readme( bool $keep_contents, string $file = 'README.md' ): void {
	$contents = file_get_contents( $file );

	if ( empty( $contents ) ) {
		return;
	}

	if ( $keep_contents ) {
		$contents = str_replace( '<!--front-end-->', '', $contents );
		$contents = str_replace( '<!--/front-end-->', '', $contents );

		file_put_contents( $file, $contents );
	} else {
		file_put_contents(
			$file,
			trim( (string) preg_replace( '/<!--front-end-->.*<!--\/front-end-->/s', '', $contents ) ?: $contents ),
		);
	}
}

/* Remove the assets.php require from the main plugin file. */
function remove_assets_require(): void {
	global $plugin_file;

	$contents = file_get_contents( $plugin_file );

	if ( empty( $contents ) ) {
		return;
	}

	file_put_contents(
		$plugin_file,
		trim( (string) preg_replace( '/require_once __DIR__ \. \'\/src\/assets.php\';\\n/s', '', $contents ) ?: $contents ) . PHP_EOL,
	);
}

/* Remove the node tests from within the all-pr-tests.yml file. */
function remove_assets_test(): void {
	$file = __DIR__ . '/.github/workflows/all-pr-tests.yml';

	if ( ! file_exists( $file ) ) {
		return;
	}


	$contents = preg_replace(
		'/(- name: Run Node Tests.*)(- name:)/s',
		'$2',
		file_get_contents( $file ),
	);

	file_put_contents( $file, $contents );
}

function determine_separator( string $path ): string {
	return str_replace( '/', DIRECTORY_SEPARATOR, $path );
}

/**
 * @return array<int, string>
 */
function list_all_files_for_replacement(): array {
	$exclude = [
		'LICENSE',
		'configure.php',
		'.phpunit.result.cache',
		'.phpcs',
		'composer.lock',
	];

	$exclude_dirs = [
		'.git',
		'pantheon-mu-plugin',
		'vendor',
		'node_modules',
		'.phpcs',
		'.scaffolder',
	];

	$exclude = array_map(
		fn ( string $file ) => "--exclude {$file}",
		$exclude,
	);

	$exclude_dirs = array_map(
		fn ( string $dir ) => "--exclude-dir {$dir}",
		$exclude_dirs,
	);

	return explode(
		PHP_EOL,
		run(
			"grep -R -l . " . implode( ' ', $exclude_dirs ) . ' ' . implode( ' ', $exclude ),
		),
	);
}

/**
 * @param string|array<int, string> $paths
 */
function delete_files( string|array $paths ): void {
	if ( ! is_array( $paths ) ) {
		$paths = [ $paths ];
	}

	foreach ( $paths as $path ) {
		$path = determine_separator( $path );

		if ( is_dir( $path ) ) {
			run( "rm -rf {$path}" );
		} elseif ( file_exists( $path ) ) {
			@unlink( $path );
		}
	}
}

function remove_phpstan(): void {
	delete_files( 'phpstan.neon' );

	// Manually patch the Composer.json file.
	if ( file_exists( 'composer.json' ) ) {
		$composer_json = (array) json_decode( (string) file_get_contents( 'composer.json' ), true );

		if ( isset( $composer_json['scripts']['phpstan'] ) ) { // @phpstan-ignore-line
			unset( $composer_json['scripts']['phpstan'] ); // @phpstan-ignore-line

			$composer_json['scripts']['test'] = [ // @phpstan-ignore-line
				'@phpcs',
				'@phpunit',
			];

			file_put_contents( 'composer.json', json_encode( $composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}
	}
}

function contributing_message( string $message ): void {
	write( "\n{$message}\n" );
	echo "\t\e]8;;https://github.com/alleyinteractive/.github/blob/main/CONTRIBUTING.md#best-practices\e\\CONTRIBUTING.md\e]8;;\e\\\n\n";
}

/* Enable SQLite testing for the plugin with Mantle Testkit. */
function enable_sqlite_testing(): void {
	if ( ! file_exists( __DIR__ . '/phpunit.xml' ) ) {
		return;
	}

	file_put_contents(
		__DIR__ . '/phpunit.xml',
		str_replace(
			[
				'<!-- <env name="MANTLE_USE_SQLITE" value="true" /> -->',
				'<!-- <env name="WP_SKIP_DB_CREATE" value="true" /> -->',
			],
			[
				'<env name="MANTLE_USE_SQLITE" value="true" />',
				'<env name="WP_SKIP_DB_CREATE" value="true" />',
			],
			(string) file_get_contents( __DIR__ . '/phpunit.xml' ),
		),
	);

	if ( file_exists( __DIR__ . '/.github/workflows/all-pr-tests.yml' ) ) {
		file_put_contents(
			__DIR__ . '/.github/workflows/all-pr-tests.yml',
			str_replace(
				'skip-wordpress-install:',
				"skip-services: 'true'\n          skip-wordpress-install:",
				(string) file_get_contents( __DIR__ . '/.github/workflows/all-pr-tests.yml' ),
			),
		);
	}
}

// ---------------------------------------------------------
// Start of the script. Above this line are the functions.
// ---------------------------------------------------------

echo "\nWelcome friend to alleyinteractive/create-wordpress-plugin! 😀\nLet's setup your WordPress Plugin 🚀\n\n";

// Always delete the 'merge-develop-to-scaffold.yml' file (this is never used in
// a scaffolded plugins).
delete_files( '.github/workflows/merge-develop-to-scaffold.yml' );

$current_dir = getcwd();

if ( ! $current_dir ) {
	echo "Could not determine current directory.\n";
	exit( 1 );
}

$folder_name = ensure_capitalp( basename( $current_dir ) );

$author_email = ask(
	question: 'Author email?',
	default: (string) ( $args['author_email'] ?? run( 'git config user.email' ) ),
	allow_empty: false,
);

$username_guess  = explode( ':', run( 'git config remote.origin.url' ) )[1] ?? '';
$username_guess  = dirname( $username_guess );
$username_guess  = basename( $username_guess );
$author_username = ask(
	question: 'Author username?',
	default: $username_guess,
	allow_empty: false,
);

$author_name = ask(
	question: 'Author name?',
	default: (string) ( $args['author_name'] ?? run( 'git config user.name' ) ),
	allow_empty: false,
);

$vendor_name = ask(
	question: 'Vendor name (usually the Github Organization)?',
	default: $username_guess,
	allow_empty: false,
);

$vendor_slug     = slugify( $vendor_name );
$is_alley_plugin = 'alleyinteractive' === $vendor_slug;

$plugin_name = ask(
	question: 'Plugin name?',
	default: (string) ( $args['plugin_name'] ?? str_replace( '_', ' ', title_case( $folder_name ) ) ),
	allow_empty: false,
);

while ( true ) {
	$plugin_name_slug = slugify( ask(
		question: 'Plugin slug?',
		default: 'wp-' . ltrim( slugify( $plugin_name ), 'wp-' ),
		allow_empty: false,
	) );

	// Suggest a different plugin name if this is an Alley plugin.
	if ( $is_alley_plugin && 0 !== strpos( $plugin_name_slug, 'wp-' ) ) {
		$example_slug = "wp-{$plugin_name_slug}";

		contributing_message( "Alley WordPress plugin slugs should be prefixed with \"wp-\". For example, {$example_slug} would be a great slug. If this plugin isn't meant to be published anywhere, this is fine to ignore. See our CONTRIBUTING.md for more details." );

		if ( ! confirm( 'Do you wish to continue anyway?', false ) ) {
			continue;
		}
	}

	break;
}

while ( true ) {
	$namespace  = ask(
		question: 'Plugin namespace?',
		default: $is_alley_plugin ? 'Alley\\WP\\' . title_case( $plugin_name ) : title_case( $plugin_name ),
		allow_empty: false,
	);

	// Check if the namespace is valid.
	if ( ! preg_match( '/^[a-zA-Z0-9_\\\\]+$/', $namespace ) ) {
		echo "Invalid namespace, please try again.\n";
		continue;
	}

	// Offer to fix the namespace if this is an Alley plugin.
	if ( $is_alley_plugin && 0 !== strpos( $namespace, 'Alley\\WP\\' ) ) {
		$example_namespace = 'Alley\\WP\\' . title_case( $plugin_name );
		contributing_message( "Alley WordPress plugins should be prefixed with \"Alley\\WP\\\". A namespace such as \"{$example_namespace}\" would work well. If this plugin isn't meant to be published anywhere, this is fine to ignore. See our CONTRIBUTING.md for more details." );

		if ( ! confirm( 'Do you wish to continue anyway?', false ) ) {
			continue;
		}
	}

	break;
}

$class_name  = ask( 'Base class name for plugin?', title_case( $plugin_name ) );
$description = ask( 'Plugin description?', "This is my plugin {$plugin_name}" );

while ( true ) {
	$plugin_file = ask( 'Main plugin file?', "{$plugin_name_slug}.php" );

	// Validate that plugin file is a valid file name.
	if ( ! preg_match( '/^[a-zA-Z0-9-_\.]+\.php$/', $plugin_file ) ) {
		echo "Invalid plugin file name. Please try again.\n";
		continue;
	}

	// Validate that plugin file does not already exist.
	if ( file_exists( $plugin_file ) ) {
		echo "Plugin file already exists. Please try again.\n";
		continue;
	}

	break;
}

write( '------' );
write( "Plugin      : {$plugin_name} <{$plugin_name_slug}>" );
write( "Author      : {$author_name} ({$author_email})" );
write( "Vendor      : {$vendor_name} ({$vendor_slug})" );
write( "Description : {$description}" );
write( "Namespace   : {$namespace}" );
write( "Main File   : {$plugin_file}" );
write( "Main Class  : {$class_name}" );
write( '------' );

write( 'This script will replace the above values in all relevant files in the project directory.' );

if ( ! confirm( 'Modify files?', true ) ) {
	exit( 1 );
}

$search_and_replace = [
	'author_name'                 => $author_name,
	'author_username'             => $author_username,
	'email@domain.com'            => $author_email,

	'A skeleton WordPress plugin' => $description,

	// Extra slashes are here for composer.json.
	'Alley\\\WP\\\Create_WordPress_Plugin\\\\' => str_replace( '\\', '\\\\', $namespace ) . '\\\\',
	'Alley\WP\Create_WordPress_Plugin'         => $namespace,

	'Example_Plugin'              => $class_name,
	'create_wordpress_plugin'     => str_replace( '-', '_', $plugin_name_slug ),
	'plugin_name'                 => $plugin_name,

	'create-wordpress-plugin'     => $plugin_name_slug,
	'Create WordPress Plugin'     => $plugin_name,

	'CREATE_WORDPRESS_PLUGIN'     => strtoupper( str_replace( '-', '_', $plugin_name_slug ) ),
	'Skeleton'                    => $class_name,
	'vendor_name'                 => $vendor_name,
	'alleyinteractive'            => $vendor_slug,
	'plugin.php'                  => $plugin_file,
];

foreach ( list_all_files_for_replacement() as $path ) {
	echo "Updating $path...\n";

	replace_in_file( $path, $search_and_replace );

	if ( str_contains( $path, determine_separator( 'src/class-example-plugin.php' ) ) ) {
		rename( $path, determine_separator( './src/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php' ) );
	}

	if ( str_contains( $path, 'README.md' ) ) {
		remove_readme_paragraphs( $path );
	}
}

// Attempt to rename the main plugin file.
if ( 'plugin.php' !== $plugin_file ) {
	rename( 'plugin.php', $plugin_file );

	replace_in_file( './.github/workflows/upgrade-wordpress-plugin.yml', $search_and_replace );

	echo "Renamed plugin.php to {$plugin_file}\n";
}

echo "Done!\n\n";

$needs_built_assets = false;
$uses_composer      = false;

if ( confirm( 'Will this plugin be compiling front-end assets (Node)?', true ) ) {
	$needs_built_assets = true;

	if ( confirm( 'Do you want to run `npm install` and `npm run build`?', true ) ) {
		echo run( 'npm install && npm run build' );
		echo "\n\n\n";
	}

	remove_assets_readme( keep_contents: true );
} elseif ( confirm( 'Do you want to delete the front-end files? (Such as package.json, etc.)', true ) ) {
	echo "Deleting...\n";

	delete_files(
		[
			'.eslintignore',
			'.eslintrc.json',
			'.nvmrc',
			'.stylelintrc.json',
			'babel.config.js',
			'jest.config.js',
			'jsconfig.json',
			'package.json',
			'package-lock.json',
			'tsconfig.json',
			'entries/',
			'blocks/',
			'build/',
			'bin/',
			'node_modules/',
			'scaffold',
			'src/assets.php',
		]
	);

	remove_assets_readme( keep_contents: false );
	remove_assets_require();
	remove_assets_test();
}

if ( confirm( 'Will this plugin be using Composer? (WordPress Composer Autoloader is already included! phpcs and phpunit also rely on Composer being installed for testing.)', true ) ) {
	$uses_composer = true;
	$needs_built_assets = true;

	remove_composer_wrapper_comments();

	if ( confirm( 'Do you want to run `composer install`?', true ) ) {
		if ( file_exists( __DIR__ . '/composer.lock' ) ) {
			echo run( 'composer update' );
		} else {
			echo run( 'composer install' );
		}

		echo "\n\n";
	}
} elseif ( confirm( 'Do you want to remove the vendor/autoload.php dependency from your main plugin file and the composer.json file?' ) ) {
	remove_composer_require();

	// Prompt the user to delete the composer.json file. Plugins often still
	// keep this around for development and Packagist.
	if ( confirm( 'Do you want to delete the composer.json and composer.lock files? (This will prevent you from using PHPCS/PHPStan/Composer entirely).', false ) ) {
		remove_composer_files();
	}
}

if ( file_exists( 'composer.json') && ! confirm(' Using PHPStan? (PHPStan is a great static analyzer to help find bugs in your code.)', true) ) {
	remove_phpstan();
}

$standalone = true;

// Check if the plugin will be use standalone (as a single repository) or as a
// part of larger project (such as a wp-content-rooted project). Assumes that
// the parent project is located at /wp-content/ and this plugin is located at
// /wp-content/plugins/:plugin/.
if (
	file_exists( '../../.git/index' )
	&& is_dir( '../../../wp-admin' )
	&& ! confirm(
		'Will this be a standalone plugin, not located within a larger project? For example, a standalone plugin will have a separate repository and will be distributed independently.',
		false,
	)
) {
	$standalone = false;

	$needs_built_assets = false;

	if ( confirm( 'Do you want to remove project-based files, such as GitHub actions? (If this is a standalone plugin, these are probably in the root directory.)', true ) ) {
		remove_project_files();
	}

	// Offer to roll up this plugin's dependencies to the parent project's composer.
	if ( $uses_composer && file_exists( '../../composer.json' ) ) {
		$parent_composer = (string) realpath( '../../composer.json' );
		$parent_folder   = dirname( $parent_composer );

		if ( confirm( "Do you want to rollup the plugin's Composer dependencies to the parent project's composer.json file ({$parent_composer})? This will copy this plugin's dependencies to the parent project and delete the local composer.json file.", true ) ) {
			$composer        = (array) json_decode( (string) file_get_contents( $parent_composer ), true );
			$plugin_composer = (array) json_decode( (string) file_get_contents( 'composer.json' ), true );

			$original = $composer;

			$composer['require']     = array_merge(
				(array) ( $composer['require'] ?? [] ),
				(array) ( $plugin_composer['require'] ?? [] ),
			);

			$composer['require-dev'] = array_merge(
				(array) ( $composer['require-dev'] ?? [] ),
				(array) ( $plugin_composer['require-dev'] ?? [] ),
			);

			$composer['config']['allow-plugins']['alleyinteractive/composer-wordpress-autoloader'] = true;

			ksort( $composer['require'] );
			ksort( $composer['require-dev'] );
			ksort( $composer['config']['allow-plugins'] );

			if ( $composer !== $original ) {
				file_put_contents( $parent_composer, json_encode( $composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
				echo "Updated {$parent_composer} with the plugin's composer dependencies.\n";

				remove_composer_require();
				remove_composer_files();

				echo "\n\n";

				if ( confirm( "Do you want to run `composer update` in {$parent_folder}?", true ) ) {
					echo run( 'composer update', $parent_folder );
					echo "\n\n";
				}
			}

			$parent_files = [
				$parent_folder . '/phpcs.xml',
				$parent_folder . '/phpcs.xml.dist',
				$parent_folder . '/.phpcs.xml',
			];

			if ( file_exists( __DIR__ . '/.phpcs.xml' ) ) {
				foreach ( $parent_files as $parent_file ) {
					if ( ! file_exists( $parent_file ) ) {
						continue;
					}

					if ( confirm( "Do you want to roll up the phpcs configuration to the parent? (This will change the plugin's phpcs configuration to inherit the parent configuration from {$parent_file}.)" ) ) {
						rollup_phpcs_to_parent(
							parent_file: '../../' . basename( $parent_file ),
							local_file: __DIR__ . '/.phpcs.xml',
							plugin_name: $plugin_name,
							plugin_domain: $plugin_name_slug,
						);

						break;
					}
				}
			}
		}
	}

	if ( confirm( 'Do you want to remove the git repository for the plugin?', true ) ) {
		delete_files( '.git' );
	}
}

if ( $standalone && confirm( 'Do you want to use SQLite for unit testing? (This is a great way to speed up your tests!)', true ) ) {
	enable_sqlite_testing();
}

// Offer to delete the built asset workflows if built assets aren't needed.
if ( ! $needs_built_assets && file_exists( '.github/workflows/built-release.yml' ) && confirm( 'Delete the Github actions for built assets?', true ) ) {
	delete_files(
		[
			'.github/workflows/built-branch.yml',
			'.github/workflows/built-release.yml',
		]
	);
}

if ( confirm( 'Let this script delete itself?', true ) ) {
	delete_files(
		[
			'Makefile',
			__FILE__,
		]
	);
}

echo "\n\nWe're done! 🎉\n\n";

// Offer some information about built releases if the workflow still exists.
if ( file_exists( '.github/workflows/built-release.yml' ) ) {
	echo <<<INFO
When you are ready to release the plugin, you can run `npm run release`
to generate a new release.

The Built Release workflow will take care of the rest by building the plugin's
front-end assets (if any), pushing up a tag, and creating a GitHub release for
the new version.

For more information, check out the README file:

	https://github.com/alleyinteractive/.github#built-releases

INFO;
}

die( 0 );
