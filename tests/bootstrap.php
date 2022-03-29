<?php
/**
 * plugin_name Test Bootstrap
 */

use function Mantle\Testing\tests_add_filter;

/**
 * Visit {@see https://mantle.alley.co/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\install(
	function() {
		tests_add_filter(
			'muplugins_loaded',
			function() {
				// Load the main file of the plugin.
				require_once __DIR__ . '/../plugin.php';
			}
		);
	}
);
