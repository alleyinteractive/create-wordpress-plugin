<?php
/**
 * Slotfills script registration and enqueue.
 *
 * This file will be copied to the assets build directory.
 *
 * @package create-wordpress-plugin
 */

namespace Create_WordPress_Plugin;

add_action(
	'enqueue_block_editor_assets',
	__NAMESPACE__ . '\action_enqueue_example_slotfills_assets'
);

/**
 * Registers all slotfill assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 */
function register_example_slotfills_scripts(): void {
	/*
	|--------------------------------------------------------------------------
	| Register slotfills assets from entry.
	|--------------------------------------------------------------------------
	|
	| This example function is called by the enqueue_block_editor_assets hook. Use it to
	| enqueue assets that are loaded in the block editor.
	|
	| In the example below we can use the __FILE__ path to find the asset and enqueue
	| under any condition. This file, index.php, will be required from the  `load_scripts()`
	| function.
	|
	| Registering or enqueuing from the entry directory allows a developer to enqueue assets
	| within the context of an entry point.
	|
	*/
	// Automatically load dependencies and version.
	$asset_file = include __DIR__ . '/index.asset.php';

	/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
	 * wp_register_script(
	 *     'create-wordpress-plugin-example_slotfills',
	 *     plugins_url( 'index.js', __FILE__ ),
	 *     $asset_file['dependencies'],
	 *     $asset_file['version'],
	 *     true
	 * );
	 * wp_set_script_translations( 'create-wordpress-plugin-example_slotfills', 'create-wordpress-plugin' );
	 */
}
add_action( 'init', __NAMESPACE__ . '\register_example_slotfills_scripts' );

/**
 * Enqueue block editor assets for this slotfill.
 */
function action_enqueue_example_slotfills_assets(): void {
	wp_enqueue_script( 'create-wordpress-plugin-example_slotfills' );
}
