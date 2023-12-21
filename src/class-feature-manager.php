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
	private array $features;

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
        if ( empty( $this->features ) ) {
            $this->features = [];
        }
        $this->autoload_features( CREATE_WORDPRESS_PLUGIN_DIR . '/features' );

        foreach ( $features_to_create as $feature_class => $args ) {
            $this->features[] = new $feature_class( ...$args );
        }
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
        // if ( empty( $this->features ) ) {
        //     $this->features = [];
        // }
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
     * @param Feature $feature The new feature.
     * @return void
     */
    public function add_feature( Feature $feature ) {
        $this->features[] = $feature;
        if ( $this->booted ) {
            $feature->boot();
        }
    }

    /**
     * Get a feature by name.
     *
     * @param string $feature_name The name of the feature to get.
     * @return Feature|null The feature or null if it doesn't exist.
     */
    public function get_feature( string $feature_name ) {
        foreach ( $this->features as $feature ) {
            if ( $feature->get_name() === $feature_name ) {
                return $feature;
            }
        }
        return null;
    }

    /**
     * Autoload features from a directory.
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
