<?php
/**
 * Register_Block_Manifest class file
 *
 * @package create-wordpress-plugin
 */

declare(strict_types=1);

namespace Alley\WP\Create_WordPress_Plugin\Features;

use Alley\WP\Types\Feature;

/**
 * Register WordPress Blocks from the `block-manifest.php` file.
 */
class Register_Block_Manifest implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Check if the blocks manifest file exists.
		if ( ! file_exists( CREATE_WORDPRESS_PLUGIN_DIR . '/build/blocks-manifest.php' ) ) {
			return;
		}

		/**
		 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
		 * based on the registered block metadata.
		 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
		 *
		 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
		 */
		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( CREATE_WORDPRESS_PLUGIN_DIR . '/build', CREATE_WORDPRESS_PLUGIN_DIR . '/build/blocks-manifest.php' );
			return;
		}

		/**
		 * Registers the block(s) metadata from the `blocks-manifest.php` file.
		 * Added to WordPress 6.7 to improve the performance of block type registration.
		 *
		 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
		 */
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection( CREATE_WORDPRESS_PLUGIN_DIR . '/build', CREATE_WORDPRESS_PLUGIN_DIR . '/build/blocks-manifest.php' );
		}

		/**
		 * Registers the block type(s) in the `blocks-manifest.php` file.
		 *
		 * @see https://developer.wordpress.org/reference/functions/register_block_type/
		 *
		 * @var array<string, array<string, mixed>> $manifest_data The blocks manifest data.
		 */
		$manifest_data = require CREATE_WORDPRESS_PLUGIN_DIR . '/build/blocks-manifest.php';

		foreach ( array_keys( $manifest_data ) as $block_type ) {
			register_block_type( CREATE_WORDPRESS_PLUGIN_DIR . "/build/{$block_type}" );
		}
	}
}
