<?php
/**
 * Class to manage plugin features.
 *
 * @package create-wordpress-plugin
 */

namespace Create_WordPress_Plugin;

use \Alley\WP\Types\Feature;

class Feature_Manager {
	/**
	 * Collected features.
	 *
	 * @var Feature[]
	 */
	private static array $features = [];

    /**
	 * Set up.
	 *
	 * @param array ...$features_to_create Array of feature classnames and arguments.
	 */
	public static function add_features( array $features_to_create = [] ) {
        foreach ( $features_to_create as $feature_class => $args ) {
            self::add_feature( $feature_class, $args );
        }
	}

    /**
     * Return the plugin features.
     *
     * @return Feature[] The plugin features.
     */
    public static function get_features() {
        return self::$features;
    }

    /**
     * Add a feature to the plugin.
     *
     * @param string $feature_class The feature class to add.
     * @param array  $args          The arguments to pass to the feature constructor.
     * @return Feature The feature that was added.
     */
    public static function add_feature( string $feature_class, array $args = [] ) {
        $feature = new $feature_class( ...$args );
        self::$features[] = $feature;
        $feature->boot();
        return $feature;
    }

    /**
     * Get a feature by class name.
     *
     * @param string $feature_name The name of the feature to get.
     * @return Feature|null The feature or null if it doesn't exist.
     */
    public static function get_feature( string $feature_name ) {
        foreach ( self::$features as $feature ) {
            if ( get_class( $feature ) === $feature_name ) {
                return $feature;
            }
        }
        return null;
    }
}
