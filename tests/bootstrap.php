<?php
/**
 * Create WordPress Plugin Tests: Bootstrap
 *
 * phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
 *
 * @package create-wordpress-plugin
 */

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Rsync the plugin to plugins/create-wordpress-plugin when testing.
	->maybe_rsync_plugin()

	// Use SQLite for testing instead of SQL (disabled by default).
	// ->with_sqlite()

	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../plugin.php' )
	->install();
