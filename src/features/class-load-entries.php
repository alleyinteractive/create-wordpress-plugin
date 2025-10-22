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
	 * Boot the feature.
	 */
	public function boot(): void {
		$files = glob( CREATE_WORDPRESS_PLUGIN_DIR . '/build/**/index.php' );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $path ) {
			if ( validate_path( $path ) ) {
				require_once $path;  // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}
	}
}
