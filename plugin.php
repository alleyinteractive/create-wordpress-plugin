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

namespace Alley\WP\Create_WordPress_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'CREATE_WORDPRESS_PLUGIN_DIR', __DIR__ );

/* Start Composer Loader */

if ( file_exists( __DIR__ . '/vendor-prefixed/vendor/scoper-autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/vendor/scoper-autoload.php';
	require_once __DIR__ . '/vendor-prefixed/vendor/wordpress-autoload.php';
	// require_once __DIR__ . '/vendor-prefixed/vendor/wordpress-autoload.php';
} elseif ( file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
} elseif ( ! class_exists( \Composer\InstalledVersions::class ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
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

/* End Composer Loader */

// Load the plugin's main files.
require_once __DIR__ . '/src/assets.php';
require_once __DIR__ . '/src/meta.php';
require_once __DIR__ . '/src/main.php';

load_scripts();
register_post_meta_from_defs();
main();
