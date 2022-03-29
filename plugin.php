<?php
/**
 * Plugin Name: plugin_name
 * Plugin URI: https://github.com/alleyinteractive/create-wordpress-plugin
 * Description: plugin_description
 * Version: 0.1.0
 * Author: author_name
 * Author URI: https://github.com/alleyinteractive/create-wordpress-plugin
 * Requires at least: 5.9
 * Tested up to: 5.9
 *
 * Text Domain: plugin_domain
 * Domain Path: /languages/
 *
 * @package package_name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Composer is installed.
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	\add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Composer is not installed and the plugin_name cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'plugin_domain' ); ?></p>
			</div>
			<?php
		}
	);

	return;
}

// Load Composer dependencies.
require_once __DIR__ . '/vendor/wordpress-autoload.php';
