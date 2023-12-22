<?php
/**
 * Class to manage plugin features.
 *
 * @package create-wordpress-plugin
 */

namespace Create_WordPress_Plugin;

use Alley\WP\Types\Feature;

/**
 * The Feature Manager class.
 */
class Feature_Manager {
	/**
	 * Collected features.
	 *
	 * @var object[]
	 */
	private static array $features = [];

	/**
	 * Set up.
	 *
	 * @param array $features_to_create Array of feature classnames and arguments.
	 *
	 * @phpstan-param array{string: array{string?: mixed}}|array{} $features_to_create
	 */
	public static function add_features( array $features_to_create = [] ): void {
		foreach ( $features_to_create as $feature_class => $args ) {
			self::add_feature( $feature_class, $args );
		}
	}

	/**
	 * Return the plugin features.
	 *
	 * @return object[] The plugin features.
	 */
	public static function get_features(): array {
		return self::$features;
	}

	/**
	 * Add a feature to the plugin.
	 *
	 * @param string $feature_class The feature class to add.
	 * @param array  $args          The arguments to pass to the feature constructor.
	 *
	 * @phpstan-param array{string?: mixed} $args
	 *
	 * @return object The instatiated feature that was added.
	 * @throws \Exception If the feature class does not implement Feature.
	 */
	public static function add_feature( string $feature_class, array $args = [] ): object {
		if ( ! in_array( Feature::class, class_implements( $feature_class ) ?: [], true ) ) {
			throw new \Exception( 'Feature class must implement Feature interface.' );
		}
		$feature = new $feature_class( ...$args );
		$feature->boot(); // @phpstan-ignore-line
		self::$features[] = $feature;
		return $feature;
	}

	/**
	 * Get a feature by class name.
	 *
	 * @param string $feature_name The name of the feature to get.
	 * @return object|null The feature or null if it doesn't exist.
	 */
	public static function get_feature( string $feature_name ): ?object {
		foreach ( self::$features as $feature ) {
			if ( get_class( $feature ) === $feature_name ) {
				return $feature;
			}
		}
		return null;
	}
}
