<?php
/**
 * The main plugin function
 *
 * @package create-wordpress-plugin
 */

namespace Alley\WP\Create_WordPress_Plugin;

use Alley\WP\Features\Group;

/**
 * Instantiate the plugin.
 */
function main(): void {
	$plugin = new Group(
		new Features\Register_Block_Manifest(),
		new Features\Load_Entries( cache: 'local' !== wp_get_environment_type() ),
		// Add features here.
	);

	/*
	 * Add additional features here.
	 *
	 * Example:
	 *
	 *   $plugin->include( new Features\My_New_Feature() );
	 *
	 * You can generate a new feature using `npx @alleyinteractive/scaffolder@latest feature`.
	 *
	 * @see https://github.com/alleyinteractive/wp-type-extensions
	 */

	$plugin->boot();
}
