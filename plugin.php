<?php
/**
 * Plugin Name: Create WordPress Plugin
 * Plugin URI: https://github.com/alleyinteractive/create-wordpress-plugin
 * Description: A skeleton WordPress plugin
 * Version: 0.0.0
 * Author: author_name
 * Author URI: https://github.com/alleyinteractive/create-wordpress-plugin
 * Requires at least: 5.9
 * Tested up to: 6.2
 *
 * Text Domain: create-wordpress-plugin
 * Domain Path: /languages/
 *
 * @package create-wordpress-plugin
 */

namespace Create_WordPress_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'CREATE_WORDPRESS_PLUGIN_DIR', __DIR__ );

/* Start Composer Loader */

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and create-wordpress-plugin cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'create-wordpress-plugin' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

/* End Composer Loader */

// Load the plugin's main files.
require_once __DIR__ . '/src/assets.php';
require_once __DIR__ . '/src/meta.php';
require_once __DIR__ . '/src/class-feature-manager.php';

/**
 * Instantiate the plugin.
 */
function main(): void {
	$lyrics = "Hello, Dolly
		Well, hello, Dolly
		It's so nice to have you back where you belong
		You're lookin' swell, Dolly
		I can tell, Dolly
		You're still glowin', you're still crowin'
		You're still goin' strong
		I feel the room swayin'
		While the band's playin'
		One of our old favorite songs from way back when
		So, take her wrap, fellas
		Dolly, never go away again
		Hello, Dolly
		Well, hello, Dolly
		It's so nice to have you back where you belong
		You're lookin' swell, Dolly
		I can tell, Dolly
		You're still glowin', you're still crowin'
		You're still goin' strong
		I feel the room swayin'
		While the band's playin'
		One of our old favorite songs from way back when
		So, golly, gee, fellas
		Have a little faith in me, fellas
		Dolly, never go away
		Promise, you'll never go away
		Dolly'll never go away again";

	// Add a feature using the filter.
	// add_filter( 'create_wordpress_plugin_features', function ( array $features ) use ( $lyrics ): array {
	// 	$features[ 'Create_WordPress_Plugin\Features\Hello' ] = [ 'lyrics' => $lyrics ];
	// 	return $features;
	// } );

	// Add a feature using the array on construct.
	$features =[
		// 'Create_WordPress_Plugin\Features\Hello' => [ 'lyrics' => $lyrics ],
	];
	$features = apply_filters( 'create_wordpress_plugin_features', $features );

	$feature_manager = new Feature_Manager( $features );
	// Add a feature using the add_feature method.
	$feature_manager->add_feature( 'Create_WordPress_Plugin\Features\Hello', [ 'lyrics' => $lyrics ] );
	$feature_manager->boot();
	// Get the instance of the feature.
	$hello_feature = $feature_manager->get_feature( 'Create_WordPress_Plugin\Features\Hello' );
	// Once we have the instance, we can remove hooks from inside the instance.
	// remove_action( 'admin_head', [ $hello_feature, 'dolly_css' ] );
}
main();
