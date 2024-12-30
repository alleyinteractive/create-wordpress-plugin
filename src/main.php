<?php
/**
 * The main plugin function
 *
 * @package create-wordpress-plugin
 */

namespace Alley\WP\Create_WordPress_Plugin;

use Create_WordPress_Plugin_Vendor\Alley\WP\Features\Group;

/**
 * Instantiate the plugin.
 */
function main(): void {
	// Add features here.
	$plugin = new Group();

	$plugin->boot();
}
