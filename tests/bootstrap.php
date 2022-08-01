<?php
/**
 * plugin_name Test Bootstrap
 */

use function Mantle\Testing\tests_add_filter;

/**
 * Visit {@see https://mantle.alley.co/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../plugin.php' )
	->install();
