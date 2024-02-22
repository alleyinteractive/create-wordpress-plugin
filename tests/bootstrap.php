<?php
/**
 * Create WordPress Plugin Tests: Bootstrap
 *
 * phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
 *
 * @package create-wordpress-plugin
 */

putenv( 'MANTLE_CI_BRANCH=feature/sqlite-wordpress' );

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Rsync the plugin to plugins/create-wordpress-plugin when testing.
	->maybe_rsync_plugin()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../plugin.php' )
	->install();
