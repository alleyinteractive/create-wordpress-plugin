<?php
/**
 * Contains functions for working with assets (primarily JavaScript).
 *
 * phpcs:disable phpcs:ignore Squiz.PHP.CommentedOutCode.Found
 *
 * @package create-wordpress-plugin
 */

namespace Create_WordPress_Plugin;

// Register and enqueue assets.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\action_wp_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\action_admin_enqueue_scripts' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\action_enqueue_block_editor_assets' );

/**
 * A callback for the wp_enqueue_scripts hook.
 */
function action_wp_enqueue_scripts(): void {
	/*
	|--------------------------------------------------------------------------
	| Enqueue site assets using the asset/entry helper functions.
	|--------------------------------------------------------------------------
	|
	| This function is called by the enqueue_block_editor_assets hook. Use it to
	| enqueue assets that are loaded across your site.
	|
	| For more advanced usage, check out the WordPress Asset Manager plugin by Alley.
	|
	|     https://github.com/alleyinteractive/wp-asset-manager
	|
	*/

	// wp_enqueue_script(
	// 	'create-wordpress-plugin-example-entry',
	// 	get_entry_asset_url( 'example-entry' ),
	// 	get_asset_dependency_array( 'example-entry' ),
	// 	get_asset_version( 'example-entry' ),
	// 	true
	// );
	// wp_set_script_translations( 'create-wordpress-plugin-example-entry', 'create-wordpress-plugin' );
}

/**
 * A callback for the admin_enqueue_scripts hook.
 */
function action_admin_enqueue_scripts(): void {
	/*
	|--------------------------------------------------------------------------
	| Enqueue admin assets using the asset/entry helper functions.
	|--------------------------------------------------------------------------
	|
	| This function is called by the admin_enqueue_scripts hook. Use it to enqueue
	| assets that are loaded only in the WordPress admin.
	|
	*/

	// wp_enqueue_script(
	// 	'create-wordpress-plugin-admin-handle',
	// 	get_entry_asset_url( 'admin-handle' ),
	// 	get_asset_dependency_array( 'admin-handle' ),
	// 	get_asset_version( 'admin-handle' ),
	// 	true
	// );
	// wp_set_script_translations( 'create-wordpress-plugin-admin-handle', 'create-wordpress-plugin' );
}

/**
 * A callback for the enqueue_block_editor_assets hook.
 */
function action_enqueue_block_editor_assets(): void {
	/*
	|--------------------------------------------------------------------------
	| Enqueue block editor assets using the asset/entry helper functions.
	|--------------------------------------------------------------------------
	|
	| This function is called by the enqueue_block_editor_assets hook. Use it to
	| enqueue assets that are loaded in the block editor.
	|
	*/

	// wp_enqueue_script(
	// 	'create-wordpress-plugin-slotfills',
	// 	get_entry_asset_url( 'slotfills' ),
	// 	get_asset_dependency_array( 'slotfills' ),
	// 	get_asset_version( 'slotfills' ),
	// 	true
	// );
	// wp_set_script_translations( 'create-wordpress-plugin-slotfills', 'create-wordpress-plugin' );
}

/**
 * Validate file paths to prevent a PHP error if a file doesn't exist.
 *
 * @param string $path The file path to validate.
 * @return bool        True if the path is valid and the file exists.
 */
function validate_path( string $path ) : bool {
	return 0 === validate_file( $path ) && file_exists( $path );
}

/**
 * Get the entry points directory path or public URL.
 *
 * @param string  $dir_entry_name The directory name where the entry point was defined.
 * @param boolean $dir            Optional. Whether to return the directory path or the plugin URL path. Defaults to false (returns URL).
 *
 * @return string
 */
function get_entry_dir_path( string $dir_entry_name, bool $dir = false ): string {
	// The relative path from the plugin root.
	$asset_build_dir = "/build/{$dir_entry_name}/";
	// Set the absolute file path from the root directory.
	$asset_dir_path = CREATE_WORDPRESS_PLUGIN_DIR . $asset_build_dir;

	if ( validate_path( $asset_dir_path ) ) {
		// Negotiate the base path.
		return true === $dir
			? $asset_dir_path
			: plugins_url( $asset_build_dir, __DIR__ );
	}

	return '';
}

/**
 * Get the assets dependencies and version.
 *
 * @param string $dir_entry_name The entry point directory name.
 *
 * @return array{dependencies?: string[], version?: string}
 */
function get_entry_asset_map( string $dir_entry_name ): array {
	$base_path = get_entry_dir_path( $dir_entry_name, true );

	if ( ! empty( $base_path ) ) {
		$asset_file_path = trailingslashit( $base_path ) . 'index.asset.php';

		if ( validate_path( $asset_file_path ) ) {
			return include $asset_file_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}
	}

	return [];
}

/**
 * Get the dependency array for a given asset.
 *
 * @param string $dir_entry_name The entry point directory name.
 *
 * @return array<int, string> The asset's dependency array.
 */
function get_asset_dependency_array( string $dir_entry_name ) : array {
	$asset_arr = get_entry_asset_map( $dir_entry_name );
	return $asset_arr['dependencies'] ?? [];
}

/**
 * Get the version hash for a given asset.
 *
 * @param string $dir_entry_name The entry point directory name.
 *
 * @return string The asset's version hash.
 */
function get_asset_version( string $dir_entry_name ) : string {
	$asset_arr = get_entry_asset_map( $dir_entry_name );
	return $asset_arr['version'] ?? '1.0';
}

/**
 * Get the public url for the assets entry file.
 *
 * @param string $dir_entry_name The entry point directory name.
 * @param string $filename       The asset file name including the file type extension to get the public path for.
 * @return string                The public URL to the asset, empty string otherwise.
 */
function get_entry_asset_url( string $dir_entry_name, $filename = 'index.js' ) {
	if ( empty( $filename ) ) {
		return '';
	}

	if ( validate_path( trailingslashit( get_entry_dir_path( $dir_entry_name, true ) ) . $filename ) ) {
		$entry_base_url = get_entry_dir_path( $dir_entry_name );

		if ( ! empty( $entry_base_url ) ) {
			return trailingslashit( $entry_base_url ) . $filename;
		}
	}

	return '';
}

/**
 * Load the php index files from the build directory for blocks, slotfills, and any other scripts with an index.php file.
 */
function load_scripts(): void {
	$files = glob( CREATE_WORDPRESS_PLUGIN_DIR . '/build/**/index.php' );

	if ( ! empty( $files ) ) {
		foreach ( $files as $path ) {
			if ( 0 === validate_file( $path ) && file_exists( $path ) ) {
				require_once $path;  // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}
	}
}

load_scripts();
