<?php
/**
 * Load_Entries class file
 *
 * @package create-wordpress-plugin
 */

declare(strict_types=1);

namespace Alley\WP\Create_WordPress_Plugin\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\Create_WordPress_Plugin\validate_path;

/**
 * Load the built entries from the build directory.
 *
 * Entries that include a `index.php` file will be loaded.
 */
class Load_Entries implements Feature {
	/**
	 * Constructor.
	 *
	 * @param bool $cache Whether to use APCu caching for entry files. Default true.
	 */
	public function __construct( public readonly bool $cache = false ) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		foreach ( $this->get_entry_files() as $path ) {
			if ( validate_path( $path ) ) {
				require_once $path;  // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}
	}

	/**
	 * Get the list of entry files.
	 *
	 * Uses APCu caching if available.
	 *
	 * @return string[] List of entry file paths.
	 */
	protected function get_entry_files(): array {
		if ( $this->cache && function_exists( 'apcu_fetch' ) ) {
			/**
			 * Retrieve the entries from the APCu cache.
			 *
			 * @var string[]|false
			 */
			$cache = apcu_fetch( 'create_wordpress_plugin_entries' );

			if ( is_array( $cache ) ) {
				return $cache;
			}
		}

		$files = glob( CREATE_WORDPRESS_PLUGIN_DIR . '/build/**/index.php' ) ?: [];

		if ( $this->cache && function_exists( 'apcu_store' ) ) {
			apcu_store( 'create_wordpress_plugin_entries', $files, HOUR_IN_SECONDS );
		}

		return $files;
	}
}
