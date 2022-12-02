<?php
/**
 * Autoloader file
 *
 * @package WordPress_Autoloader
 */

namespace Alley_Interactive\Autoloader;

/**
 * WordPress Autoload Generator
 */
class Autoloader {
	/**
	 * Namespace to autoload.
	 *
	 * @var string
	 */
	protected string $namespace;

	/**
	 * Root path of the namespace to load from.
	 *
	 * @var string
	 */
	protected string $root_path;

	/**
	 * Missing classes for the autoloader.
	 *
	 * @var bool[]
	 * @psalm-var array<string, bool>
	 */
	protected array $missing_classes = [];

	/**
	 * APCu cache key prefix.
	 *
	 * @var ?string
	 */
	protected ?string $apcu_prefix;

	/**
	 * Generate an autoloader for the WordPress file naming conventions.
	 *
	 * @param string $namespace Namespace to autoload.
	 * @param string $root_path Path in which to look for files.
	 * @return static Function for spl_autoload_register().
	 */
	public static function generate( string $namespace, string $root_path ): callable {
		return new static( $namespace, $root_path );
	}

	/**
	 * Constructor.
	 *
	 * @param string $namespace Namespace to register.
	 * @param string $root_path Root path of the namespace.
	 */
	public function __construct( string $namespace, string $root_path ) {
		$this->namespace = $namespace;

		// Ensure consistent root.
		$this->root_path = rtrim( $root_path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * APCu prefix to use to cache found/not-found classes, if the extension is enabled.
	 *
	 * @param string|null $prefix Prefix to use.
	 * @return static
	 */
	public function set_apcu_prefix( string $prefix ) {
		$this->apcu_prefix = function_exists( 'apcu_fetch' ) && filter_var( ini_get( 'apc.enabled' ), FILTER_VALIDATE_BOOLEAN )
			? $prefix
			: null;

		return $this;
	}

	/**
	 * The APCu prefix in use, or null if APCu caching is not enabled.
	 *
	 * @return string|null
	 */
	public function get_apcu_prefix(): ?string {
		return $this->apcu_prefix;
	}

	/**
	 * Check if a class was missing from the autoloader.
	 *
	 * @param string $classname Class to check.
	 * @return bool
	 */
	public function is_missing_class( string $classname ): bool {
		return isset( $this->missing_classes[ $classname ] );
	}

	/**
	 * Register the autoloader.
	 */
	public function register() {
		spl_autoload_register( $this );
	}

	/**
	 * Unregister the autoloader.
	 */
	public function unregister() {
		spl_autoload_unregister( $this );
	}

	/**
	 * Invoke method of the class.
	 *
	 * @param string $classname Class being autoloaded.
	 */
	public function __invoke( string $classname ) {
		// Ignore if the base namespace doesn't match.
		if ( 0 !== \strpos( $classname, $this->namespace ) ) {
			return;
		}

		// Check if the class was previously not found.
		if ( isset( $this->missing_classes[ $classname ] ) ) {
			return;
		}

		// Check if the class was previously found with APCu caching.
		if ( isset( $this->apcu_prefix ) ) {
			$hit  = false;
			$file = apcu_fetch( $this->apcu_prefix . $classname, $hit );

			if ( $hit ) {
				require_once $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
				return $file;
			}
		}

		$file = $this->find_file( $classname );

		if ( $file ) {
			require_once $file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

			// Cache the found file with APCu if enabled.
			if ( isset( $this->apcu_prefix ) ) {
				apcu_add( $this->apcu_prefix . $classname, $file );
			}
		} else {
			// Mark the class as not found to save future lookups.
			$this->missing_classes[ $classname ] = true;
		}
	}

	/**
	 * Find a file for the given class.
	 *
	 * @param string $classname Class to find.
	 * @return string|null
	 */
	protected function find_file( string $classname ): ?string {
		// Break up the classname into parts.
		$parts = \explode( '\\', $classname );

		// Retrieve the class name (last item) and convert it to a filename.
		$class = \strtolower( \str_replace( '_', '-', \array_pop( $parts ) ) );

		$base_path = '';

		// Build the base path relative to the sub-namespace.
		$sub_namespace = \substr( \implode( DIRECTORY_SEPARATOR, $parts ), \strlen( $this->namespace ) );

		if ( ! empty( $sub_namespace ) ) {
			$base_path = \str_replace( '_', '-', \strtolower( $sub_namespace ) );
		}

		// Support multiple locations since the class could be a class, trait or interface.
		$paths = [
			'%1$s' . DIRECTORY_SEPARATOR . 'class-%2$s.php',
			'%1$s' . DIRECTORY_SEPARATOR . 'trait-%2$s.php',
			'%1$s' . DIRECTORY_SEPARATOR . 'interface-%2$s.php',
			'%1$s' . DIRECTORY_SEPARATOR . 'enum-%2$s.php',
		];

		/*
		* Attempt to find the file by looping through the various paths.
		*
		* Autoloading a class will also cause a trait or interface with the
		* same fully qualified name to be autoloaded, as it's impossible to
		* tell which was requested.
		*/
		foreach ( $paths as $path ) {
			$path = $this->root_path . \sprintf( $path, $base_path, $class );

			if ( \file_exists( $path ) ) {
				return $path;
			}
		}

		return null;
	}
}
