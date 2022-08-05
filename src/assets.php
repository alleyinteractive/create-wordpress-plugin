<?php
/**
 * Contains functions for working with assets (primarily JavaScript).
 *
 * @package package_name
 */

namespace Vendor_Name\Skeleton;

define( 'CREATE_WORDPRESS_PLUGIN_ASSET_MAP', read_asset_map( dirname( __DIR__ ) . '/build/assetMap.json' ) );
define( 'CREATE_WORDPRESS_PLUGIN_ASSET_MODE', CREATE_WORDPRESS_PLUGIN_ASSET_MAP['mode'] ?? 'production' );

// Register and enqueue assets.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\action_wp_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\action_admin_enqueue_scripts' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\action_enqueue_block_editor_assets' );

/**
 * A callback for the wp_enqueue_scripts hook.
 */
function action_wp_enqueue_scripts() {
	/*
	|--------------------------------------------------------------------------
	| Enqueue site assets.
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
	// 	get_asset_path( 'example-entry.js' ),
	// 	get_asset_dependencies( 'example-entry.php' ),
	// 	get_asset_hash( 'example-entry.js' ),
	// 	true
	// );
	// inline_locale_data( 'create-wordpress-plugin-example-entry' );
}

/**
 * A callback for the admin_enqueue_scripts hook.
 */
function action_admin_enqueue_scripts() {
	/*
	|--------------------------------------------------------------------------
	| Enqueue admin assets.
	|--------------------------------------------------------------------------
	|
	| This function is called by the admin_enqueue_scripts hook. Use it to enqueue
	| assets that are loaded only in the WordPress admin.
	|
	*/

	// wp_enqueue_script(
	// 	'create-wordpress-plugin-admin-handle',
	// 	get_asset_path( 'admin-handle.js' ),
	// 	get_asset_dependencies( 'admin-handle.php' ),
	// 	get_asset_hash( 'admin-handle.js' ),
	// 	true
	// );
	// inline_locale_data( 'create-wordpress-plugin-admin-handle' );
}

/**
 * A callback for the enqueue_block_editor_assets hook.
 */
function action_enqueue_block_editor_assets() {
	/*
	|--------------------------------------------------------------------------
	| Enqueue block editor assets.
	|--------------------------------------------------------------------------
	|
	| This function is called by the enqueue_block_editor_assets hook. Use it to
	| enqueue assets that are loaded in the block editor.
	|
	*/

	// wp_enqueue_script(
	// 	'create-wordpress-plugin-slotfills',
	// 	get_asset_path( 'slotfills.js' ),
	// 	get_asset_dependencies( 'slotfills.php' ),
	// 	get_asset_hash( 'slotfills.js' ),
	// 	true
	// );
	// inline_locale_data( 'create-wordpress-plugin-slotfills' );
}

/**
 * Gets asset dependencies from the generated asset manifest.
 *
 * @param string $asset Entry point and asset type separated by a '.'.
 *
 * @return array An array of dependencies for this asset.
 */
function get_asset_dependencies( string $asset ) : array {
	// Get the path to the PHP file containing the dependencies.
	$dependency_file = get_asset_path( $asset, true );
	if ( empty( $dependency_file ) ) {
		return [];
	}

	// Ensure the filepath is valid.
	if ( ! file_exists( $dependency_file ) || 0 !== validate_file( $dependency_file ) ) {
		return [];
	}

	// Try to load the dependencies.
	// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	$dependencies = require $dependency_file;
	if ( empty( $dependencies['dependencies'] ) || ! is_array( $dependencies['dependencies'] ) ) {
		return [];
	}

	return $dependencies['dependencies'];
}

/**
 * Get the contentHash for a given asset.
 *
 * @param string $asset Entry point and asset type separated by a '.'.
 *
 * @return string The asset's hash.
 */
function get_asset_hash( string $asset ) : string {
	return get_asset_property( $asset, 'hash' )
		?? CREATE_WORDPRESS_PLUGIN_ASSET_MAP['hash']
		?? '1.0.0';
}

/**
 * Get the URL for a given asset.
 *
 * @param string  $asset Entry point and asset type separated by a '.'.
 * @param boolean $dir   Optional. Whether to return the directory path or the plugin URL path. Defaults to false (returns URL).
 *
 * @return string The asset URL.
 */
function get_asset_path( string $asset, bool $dir = false ) : string {
	// Try to get the relative path.
	$relative_path = get_asset_property( $asset, 'path' );
	if ( empty( $relative_path ) ) {
		return '';
	}

	// Negotiate the base path.
	$base_path = true === $dir
		? dirname( __DIR__ ) . '/build'
		: plugins_url( 'build', __DIR__ );

	return trailingslashit( $base_path ) . $relative_path;
}

/**
 * Get a property for a given asset.
 *
 * @param string $asset Entry point and asset type separated by a '.'.
 * @param string $prop The property to get from the entry object.
 *
 * @return string|null The asset property based on entry and type.
 */
function get_asset_property( string $asset, string $prop ) : ?string {
	/*
	 * Appending a '.' ensures the explode() doesn't generate a notice while
	 * allowing the variable names to be more readable via list().
	 */
	list( $entrypoint, $type ) = explode( '.', "$asset." );

	$asset_property = CREATE_WORDPRESS_PLUGIN_ASSET_MAP[ $entrypoint ][ $type ][ $prop ] ?? null;

	return $asset_property ? $asset_property : null;
}

/**
 * Creates a new Jed instance with specified locale data configuration.
 *
 * @param string $to_handle The script handle to attach the inline script to.
 */
function inline_locale_data( string $to_handle ) {
	// Define locale data for Jed.
	$locale_data = [
		'' => [
			'domain' => 'create-wordpress-plugin',
			'lang'   => is_admin() ? get_user_locale() : get_locale(),
		],
	];

	// Pass the Jed configuration to the admin to properly register i18n.
	wp_add_inline_script(
		$to_handle,
		'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ", 'create-wordpress-plugin' );"
	);
}

/**
 * Decode the asset map at the given file path.
 *
 * @param string $path File path.
 *
 * @return array The asset map.
 */
function read_asset_map( string $path ) : array {
	if ( file_exists( $path ) && 0 === validate_file( $path ) ) {
		ob_start();
		include $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		return json_decode( ob_get_clean(), true );
	}

	return [];
}
