<?php
/**
 * Create WordPress Plugin Tests: Bootstrap
 *
 * @package create-wordpress-plugin
 */

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->with_sqlite()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../plugin.php' )
	->install();
