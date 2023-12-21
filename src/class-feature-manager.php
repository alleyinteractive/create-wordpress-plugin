<?php
/**
 * Class to manage plugin features.
 *
 * @package create-wordpress-plugin
 */

namespace Create_WordPress_Plugin;

use \Alley\WP\Types\Feature;

class Feature_Manager implements Feature {
	/**
	 * Collected features.
	 *
	 * @var Feature[]
	 */
	private array $features = [];

    /**
     * Have the features been booted?
     *
     * @var bool $booted
     */
    private bool $booted = false;

    /**
	 * Set up.
	 *
	 * @param Feature ...$features Features.
	 */
	public function __construct( array $features_to_create = [] ) {
        $this->autoload_features( CREATE_WORDPRESS_PLUGIN_DIR . '/features' );

        foreach ( $features_to_create as $feature_class => $args ) {
            $this->features[] = new $feature_class( ...$args );
        }
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		foreach ( $this->features as $feature ) {
			$feature->boot();
		}
        $this->booted = true;
	}

    /**
     * Return the plugin features.
     *
     * @return Feature[] The plugin features.
     */
    public function get_features() {
        return $this->features;
    }

    /**
     * Add a feature to the plugin.
     *
     * @param string $feature_class The feature class to add.
     * @param array  $args          The arguments to pass to the feature constructor.
     * @return Feature The feature that was added.
     */
    public function add_feature( string $feature_class, array $args = [] ) {
        $feature = new $feature_class( ...$args );
        $this->features[] = $feature;
        if ( $this->booted ) {
            $feature->boot();
        }
        return $feature;
    }

    /**
     * Get a feature by class name.
     *
     * @param string $feature_name The name of the feature to get.
     * @return Feature|null The feature or null if it doesn't exist.
     */
    public function get_feature( string $feature_name ) {
        foreach ( $this->features as $feature ) {
            if ( get_class( $feature ) === $feature_name ) {
                return $feature;
            }
        }
        return null;
    }

    /**
     * Autoload features from a directory.
     * This only includes the files. It does not boot the features.
     *
     * @param string $path The directory path.
     * @return void
     */
    public function autoload_features( string $path ) {
        $files = glob( $path . '/*.php' );
        foreach ( $files as $file ) {
            require_once $file;
        }
    }
}
