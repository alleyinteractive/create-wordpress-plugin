<?php
/**
 * Example_Feature class file
 *
 * @package Create_WordPress_Plugin
 */

namespace Create_WordPress_Plugin\Features;

use Alley\WP\Types\Feature;

/**
 * Example Feature Feature
 */
final class Example_Feature implements Feature {
	/**
	 * Set up the feature.
	 */
	public function __construct() {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Add any actions or filters here.
	}
}
