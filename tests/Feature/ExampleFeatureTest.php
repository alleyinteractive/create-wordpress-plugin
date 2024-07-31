<?php
/**
 * Create WordPress Plugin Tests: Example Feature Test
 *
 * @package create-wordpress-plugin
 */

namespace Alley\WP\Create_WordPress_Plugin\Tests\Feature;

use Alley\WP\Create_WordPress_Plugin\Tests\TestCase;

/**
 * A test suite for an example feature.
 *
 * @link https://mantle.alley.com/testing/test-framework.html
 */
class ExampleFeatureTest extends TestCase {
	/**
	 * An example test for the example feature. In practice, this should be updated to test an aspect of the feature.
	 */
	public function test_example() {
		$this->assertTrue( true );
		$this->assertNotEmpty( home_url() );
	}
}
