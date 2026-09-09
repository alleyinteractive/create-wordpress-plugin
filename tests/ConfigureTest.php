<?php
/**
 * Create WordPress Plugin Tests: Configure Script
 *
 * Exercises configure.php end-to-end: every test copies the skeleton into a
 * temporary directory, runs the script there with a scripted set of answers,
 * and asserts on the files it leaves behind.
 *
 * This test only exists in the skeleton itself. The configure script excludes
 * it from the search and replace, so that its expectations stay readable, and
 * deletes it when it deletes itself.
 *
 * Run with `composer test:configure`. The tests are skipped when the skeleton
 * has already been configured, or when the plugin has been rsync'd into a
 * WordPress install for the Mantle test suite (which excludes .github).
 *
 * @package create-wordpress-plugin
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions
 * phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions
 * phpcs:disable WordPressVIPMinimum.Performance.FetchingRemoteData
 */

declare(strict_types=1);

namespace Alley\WP\Create_WordPress_Plugin\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase as PHPUnit_Test_Case;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests for the configure.php setup script.
 */
final class ConfigureTest extends PHPUnit_Test_Case {
	/**
	 * Tokens that should be replaced in every file the script touches.
	 *
	 * @var array<int, string>
	 */
	private const PLACEHOLDERS = [
		'A skeleton WordPress plugin',
		'CREATE_WORDPRESS_PLUGIN',
		'Create WordPress Plugin',
		'Example_Plugin',
		'Skeleton',
		'author_name',
		'author_username',
		'create-wordpress-plugin',
		'create_wordpress_plugin',
		'email@domain.com',
		'vendor_name',
	];

	/**
	 * Paths the script leaves alone, relative to the plugin root.
	 *
	 * @var array<int, string>
	 */
	private const UNTOUCHED_PATHS = [
		'.scaffolder/',
		'LICENSE',
		'composer.lock',
		'tests/ConfigureTest.php',
	];

	/**
	 * Paths that are never copied into the test workspace.
	 *
	 * @var array<int, string>
	 */
	private const SKIPPED_PATHS = [
		'.git',
		'.phpcs',
		'.phpunit.result.cache',
		'build',
		'node_modules',
		'vendor',
	];

	/**
	 * Temporary directory holding the copies of the skeleton for one test.
	 */
	private string $workspace = '';

	/**
	 * Set up the workspace, or skip when the skeleton isn't available.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! file_exists( $this->skeleton_root() . '/configure.php' ) ) {
			$this->markTestSkipped( 'configure.php is not present: this plugin has already been configured.' );
		}

		// The script deletes this workflow before it asks anything, so its
		// presence means the skeleton is intact and the placeholders are, too.
		if ( ! file_exists( $this->skeleton_root() . '/.github/workflows/merge-develop-to-scaffold.yml' ) ) {
			$this->markTestSkipped( 'The skeleton is incomplete: run these tests from the repository root with `composer test:configure`.' );
		}

		$workspace = sys_get_temp_dir() . '/create-wordpress-plugin-configure-' . bin2hex( random_bytes( 6 ) );

		mkdir( $workspace, 0777, true );

		$this->workspace = (string) realpath( $workspace );
	}

	/**
	 * Remove the workspace.
	 */
	protected function tearDown(): void {
		if ( '' !== $this->workspace ) {
			$this->delete_directory( $this->workspace );

			$this->workspace = '';
		}

		parent::tearDown();
	}

	/**
	 * The placeholder tokens should be gone from every file the script touches.
	 */
	public function test_replaces_placeholder_tokens_throughout_the_project(): void {
		$plugin = $this->configure( $this->default_answers() );

		$this->assertSame( [], $this->find_placeholders( $plugin, self::PLACEHOLDERS ) );

		$main = $this->read( $plugin . '/wp-my-cool-plugin.php' );

		$this->assertStringContainsString( 'Plugin Name: My Cool Plugin', $main );
		$this->assertStringContainsString( 'Description: A very cool plugin.', $main );
		$this->assertStringContainsString( 'Author: Test Author', $main );
		$this->assertStringContainsString( 'Text Domain: wp-my-cool-plugin', $main );
		$this->assertStringContainsString( '@package wp-my-cool-plugin', $main );
		$this->assertStringContainsString( 'namespace Test_Vendor\My_Cool_Plugin;', $main );
		$this->assertStringContainsString( "define( 'WP_MY_COOL_PLUGIN_DIR', __DIR__ );", $main );
		$this->assertStringContainsString( "require_once __DIR__ . '/src/main.php';", $main );

		$readme = $this->read( $plugin . '/README.md' );

		$this->assertStringContainsString( '# My Cool Plugin', $readme );
		$this->assertStringContainsString( 'Contributors: test-user', $readme );
		$this->assertStringContainsString( 'Tags: Test Vendor, wp-my-cool-plugin', $readme );

		// The text domain and the global prefix in the PHPCS configuration.
		$phpcs = $this->read( $plugin . '/.phpcs.xml' );

		$this->assertStringContainsString( 'PHP_CodeSniffer standard for wp-my-cool-plugin.', $phpcs );
		$this->assertStringContainsString( '<element value="wp_my_cool_plugin" />', $phpcs );

		// The prefixed cache key and constant used by the features.
		$entries = $this->read( $plugin . '/src/features/class-load-entries.php' );

		$this->assertStringContainsString( "apcu_fetch( 'wp_my_cool_plugin_entries' )", $entries );
		$this->assertStringContainsString( 'WP_MY_COOL_PLUGIN_DIR', $entries );

		// References to the renamed main plugin file.
		$this->assertStringContainsString(
			"require_once __DIR__ . '/../wp-my-cool-plugin.php'",
			$this->read( $plugin . '/tests/bootstrap.php' ),
		);
	}

	/**
	 * The namespace should be replaced in composer.json, including in the
	 * double-escaped form that JSON requires.
	 */
	public function test_replaces_the_namespace_in_composer_json(): void {
		$plugin = $this->configure( $this->default_answers() );

		$composer = $this->read_json( $plugin . '/composer.json' );

		$this->assertSame( 'test-vendor/wp-my-cool-plugin', $composer['name'] );
		$this->assertSame( 'A very cool plugin.', $composer['description'] );
		$this->assertSame( 'Test Author', $composer['authors'][0]['name'] );
		$this->assertSame( 'test@example.com', $composer['authors'][0]['email'] );
		$this->assertSame(
			[ 'Test_Vendor\\My_Cool_Plugin\\Tests\\' => 'tests' ],
			$composer['autoload-dev']['psr-4'],
		);
		$this->assertSame(
			[ 'Test_Vendor\\My_Cool_Plugin\\' => 'src' ],
			$composer['extra']['wordpress-autoloader']['autoload'],
		);
	}

	/**
	 * The main plugin file and the example class file should be renamed.
	 */
	public function test_renames_the_main_plugin_file_and_the_example_class(): void {
		$plugin = $this->configure( $this->default_answers() );

		$this->assertFileDoesNotExist( $plugin . '/plugin.php' );
		$this->assertFileExists( $plugin . '/wp-my-cool-plugin.php' );

		$this->assertFileDoesNotExist( $plugin . '/src/class-example-plugin.php' );
		$this->assertFileExists( $plugin . '/src/class-my-cool-plugin.php' );

		$class = $this->read( $plugin . '/src/class-my-cool-plugin.php' );

		$this->assertStringContainsString( 'My_Cool_Plugin class file', $class );
		$this->assertStringContainsString( 'namespace Test_Vendor\My_Cool_Plugin;', $class );
		$this->assertStringContainsString( 'class My_Cool_Plugin {', $class );

		// The workflow that upgrades the plugin knows the new file name, too.
		$this->assertStringContainsString(
			'wp-my-cool-plugin.php',
			$this->read( $plugin . '/.github/workflows/upgrade-wordpress-plugin.yml' ),
		);
	}

	/**
	 * The class file should be named for the base class name, which does not
	 * have to match the plugin name.
	 */
	public function test_renames_the_example_class_to_the_given_class_name(): void {
		$plugin = $this->configure( $this->default_answers( [ 'class_name' => 'Cool_Stuff' ] ) );

		$this->assertFileDoesNotExist( $plugin . '/src/class-example-plugin.php' );
		$this->assertFileExists( $plugin . '/src/class-cool-stuff.php' );

		$this->assertStringContainsString(
			'class Cool_Stuff {',
			$this->read( $plugin . '/src/class-cool-stuff.php' ),
		);
	}

	/**
	 * Every value but the author details can be derived from the folder name.
	 */
	public function test_derives_the_defaults_from_the_folder_name(): void {
		$plugin = $this->configure(
			$this->default_answers(
				[
					'vendor_name' => 'alleyinteractive',
					'plugin_name' => '',
					'plugin_slug' => '',
					'namespace'   => '',
					'class_name'  => '',
					'description' => '',
					'plugin_file' => '',
				]
			),
			'wp-fancy-wordpress-widgets',
		);

		$main = $this->read( $plugin . '/wp-fancy-wordpress-widgets.php' );

		// The capital P in WordPress survives the title casing.
		$this->assertStringContainsString( 'Plugin Name: Wp Fancy WordPress Widgets', $main );
		$this->assertStringContainsString( 'Description: This is my plugin Wp Fancy WordPress Widgets', $main );
		$this->assertStringContainsString( 'Text Domain: wp-fancy-wordpress-widgets', $main );
		$this->assertStringContainsString( 'namespace Alley\WP\Wp_Fancy_WordPress_Widgets;', $main );
		$this->assertStringContainsString( "define( 'WP_FANCY_WORDPRESS_WIDGETS_DIR', __DIR__ );", $main );

		$this->assertFileExists( $plugin . '/src/class-wp-fancy-wordpress-widgets.php' );
	}

	/**
	 * The scaffold-only workflow, the script and its test should be cleaned up.
	 */
	public function test_deletes_the_scaffold_workflow_and_itself(): void {
		$plugin = $this->configure( $this->default_answers() );

		$this->assertFileDoesNotExist( $plugin . '/.github/workflows/merge-develop-to-scaffold.yml' );
		$this->assertFileExists( $plugin . '/.github/workflows/all-pr-tests.yml' );

		$this->assertFileDoesNotExist( $plugin . '/configure.php' );
		$this->assertFileDoesNotExist( $plugin . '/Makefile' );
		$this->assertFileDoesNotExist( $plugin . '/tests/ConfigureTest.php' );

		$scripts = $this->read_json( $plugin . '/composer.json' )['scripts'];

		$this->assertArrayNotHasKey( 'test:configure', $scripts );
		$this->assertNotContains( '@test:configure', $scripts['test'] );
		$this->assertContains( '@phpunit', $scripts['test'] );
	}

	/**
	 * Declining the self-deletion should leave the script and its test behind.
	 */
	public function test_keeps_itself_when_the_self_deletion_is_declined(): void {
		$plugin = $this->configure( $this->default_answers( [ 'delete_configure' => 'no' ] ) );

		$this->assertFileExists( $plugin . '/configure.php' );
		$this->assertFileExists( $plugin . '/Makefile' );
		$this->assertFileExists( $plugin . '/tests/ConfigureTest.php' );

		// This file is excluded from the search and replace, so that it can
		// still describe the skeleton after the script has run.
		$this->assertStringContainsString(
			'create-wordpress-plugin',
			$this->read( $plugin . '/tests/ConfigureTest.php' ),
		);

		$scripts = $this->read_json( $plugin . '/composer.json' )['scripts'];

		$this->assertArrayHasKey( 'test:configure', $scripts );
		$this->assertContains( '@test:configure', $scripts['test'] );
	}

	/**
	 * The front-end files should be removed when Node isn't being used.
	 */
	public function test_removes_the_front_end_files_when_node_is_declined(): void {
		$plugin = $this->configure(
			$this->base_answers(
				[
					'compiling_assets' => 'no',
					'delete_front_end' => 'yes',
					'using_composer'   => 'yes',
					'composer_install' => 'no',
					'using_phpstan'    => 'yes',
					'sqlite_testing'   => 'no',
					'delete_configure' => 'yes',
				]
			),
		);

		$deleted = [
			'.eslintignore',
			'.eslintrc.json',
			'.nvmrc',
			'.stylelintrc.json',
			'babel.config.js',
			'blocks',
			'entries',
			'jest.config.js',
			'jsconfig.json',
			'package-lock.json',
			'package.json',
			'scaffold',
			'src/assets.php',
			'tsconfig.eslint.json',
			'tsconfig.json',
		];

		foreach ( $deleted as $path ) {
			$this->assertFileDoesNotExist( $plugin . '/' . $path );
		}

		// The plugin file should no longer load the asset helpers.
		$main = $this->read( $plugin . '/wp-my-cool-plugin.php' );

		$this->assertStringNotContainsString( 'src/assets.php', $main );
		$this->assertStringNotContainsString( 'load_scripts();', $main );
		$this->assertStringContainsString( "require_once __DIR__ . '/src/main.php';", $main );

		// The front-end documentation should be gone from the README.
		$readme = $this->read( $plugin . '/README.md' );

		$this->assertStringNotContainsString( '<!--front-end-->', $readme );
		$this->assertStringNotContainsString( '## The `entries` directory and entry points', $readme );
		$this->assertStringContainsString( '## Testing', $readme );

		// The front-end paths should be commented out of the PHPStan config.
		$phpstan = $this->read( $plugin . '/phpstan.neon' );

		$this->assertStringContainsString( '# - blocks/', $phpstan );
		$this->assertStringContainsString( '# - entries/', $phpstan );

		// The Node test step should be gone from the workflow.
		$workflow = $this->read( $plugin . '/.github/workflows/all-pr-tests.yml' );

		$this->assertStringNotContainsString( 'Run Node Tests', $workflow );
		$this->assertStringContainsString( 'Run PHP Tests', $workflow );

		$this->assertArrayNotHasKey( 'dev', $this->read_json( $plugin . '/composer.json' )['scripts'] );
	}

	/**
	 * The front-end documentation should be kept when Node is being used, with
	 * only the markers removed.
	 */
	public function test_keeps_the_front_end_files_when_node_is_used(): void {
		$plugin = $this->configure( $this->default_answers() );

		$this->assertFileExists( $plugin . '/package.json' );
		$this->assertFileExists( $plugin . '/src/assets.php' );
		$this->assertDirectoryExists( $plugin . '/entries' );

		$readme = $this->read( $plugin . '/README.md' );

		$this->assertStringNotContainsString( '<!--front-end-->', $readme );
		$this->assertStringNotContainsString( '<!--/front-end-->', $readme );
		$this->assertStringContainsString( '## The `entries` directory and entry points', $readme );

		// The paragraphs about the skeleton itself are always removed.
		$this->assertStringNotContainsString( '<!--delete-->', $readme );
		$this->assertStringNotContainsString( 'Press the "Use template" button', $readme );
	}

	/**
	 * The Composer loader should be removed from the plugin file when Composer
	 * isn't being used, along with the built asset workflows.
	 */
	public function test_removes_composer_support_when_declined(): void {
		$plugin = $this->configure(
			$this->base_answers(
				[
					'compiling_assets'       => 'no',
					'delete_front_end'       => 'no',
					'using_composer'         => 'no',
					'remove_autoload'        => 'yes',
					'delete_composer_files'  => 'yes',
					'sqlite_testing'         => 'no',
					'delete_built_workflows' => 'yes',
					'delete_configure'       => 'yes',
				]
			),
		);

		$this->assertFileDoesNotExist( $plugin . '/composer.json' );
		$this->assertFileDoesNotExist( $plugin . '/composer.lock' );

		$main = $this->read( $plugin . '/wp-my-cool-plugin.php' );

		$this->assertStringNotContainsString( 'Composer Loader', $main );
		$this->assertStringNotContainsString( 'wordpress-autoload.php', $main );
		$this->assertStringContainsString( "require_once __DIR__ . '/src/main.php';", $main );

		// Without built assets, the release workflows are offered up.
		$this->assertFileDoesNotExist( $plugin . '/.github/workflows/built-branch.yml' );
		$this->assertFileDoesNotExist( $plugin . '/.github/workflows/built-release.yml' );

		// The front-end files were kept.
		$this->assertFileExists( $plugin . '/package.json' );
	}

	/**
	 * Keeping Composer should keep the loader but remove its wrapper comments.
	 */
	public function test_keeps_the_composer_loader_without_its_wrapper_comments(): void {
		$plugin = $this->configure( $this->default_answers() );

		$main = $this->read( $plugin . '/wp-my-cool-plugin.php' );

		$this->assertStringNotContainsString( 'Composer Loader', $main );
		$this->assertStringContainsString( "require_once __DIR__ . '/vendor/wordpress-autoload.php';", $main );

		$this->assertFileExists( $plugin . '/composer.json' );
		$this->assertFileExists( $plugin . '/.github/workflows/built-release.yml' );
	}

	/**
	 * PHPStan should be removed from the project when it isn't wanted.
	 */
	public function test_removes_phpstan_when_declined(): void {
		$plugin = $this->configure(
			$this->base_answers(
				[
					'compiling_assets' => 'no',
					'delete_front_end' => 'no',
					'using_composer'   => 'yes',
					'composer_install' => 'no',
					'using_phpstan'    => 'no',
					'sqlite_testing'   => 'no',
					'delete_configure' => 'yes',
				]
			),
		);

		$this->assertFileDoesNotExist( $plugin . '/phpstan.neon' );

		$composer = $this->read_json( $plugin . '/composer.json' );

		$this->assertArrayNotHasKey( 'szepeviktor/phpstan-wordpress', $composer['require-dev'] );
		$this->assertArrayNotHasKey( 'phpstan', $composer['scripts'] );
		$this->assertNotContains( '@phpstan', $composer['scripts']['lint'] );
		$this->assertContains( '@phpcs', $composer['scripts']['lint'] );
	}

	/**
	 * SQLite testing should be enabled on request.
	 */
	public function test_enables_sqlite_testing_when_requested(): void {
		$plugin = $this->configure( $this->default_answers() );

		$phpunit = $this->read( $plugin . '/phpunit.xml' );

		$this->assertStringContainsString( '<env name="MANTLE_USE_SQLITE" value="true" />', $phpunit );
		$this->assertStringContainsString( '<env name="WP_SKIP_DB_CREATE" value="true" />', $phpunit );
		$this->assertStringNotContainsString( '<!-- <env name="MANTLE_USE_SQLITE"', $phpunit );

		$this->assertStringContainsString(
			"skip-services: 'true'",
			$this->read( $plugin . '/.github/workflows/all-pr-tests.yml' ),
		);
	}

	/**
	 * SQLite testing should be left commented out when it isn't wanted.
	 */
	public function test_leaves_sqlite_testing_alone_when_declined(): void {
		$plugin = $this->configure( $this->default_answers( [ 'sqlite_testing' => 'no' ] ) );

		$this->assertStringContainsString(
			'<!-- <env name="MANTLE_USE_SQLITE" value="true" /> -->',
			$this->read( $plugin . '/phpunit.xml' ),
		);
		$this->assertStringNotContainsString(
			"skip-services: 'true'",
			$this->read( $plugin . '/.github/workflows/all-pr-tests.yml' ),
		);
	}

	/**
	 * A plugin inside a larger project should hand its dependencies and its
	 * PHPCS configuration off to the parent project.
	 */
	public function test_rolls_the_plugin_up_into_a_parent_project(): void {
		$parent = $this->create_parent_project();
		$plugin = $this->install_skeleton( 'wp-content/plugins/wp-my-cool-plugin' );

		// Give the plugin a repository of its own to remove.
		mkdir( $plugin . '/.git' );
		touch( $plugin . '/.git/config' );

		$result = $this->run_configure(
			$this->base_answers(
				[
					'vendor_name'            => 'alleyinteractive',
					'namespace'              => 'Alley\\WP\\My_Cool_Plugin',
					'compiling_assets'       => 'no',
					'delete_front_end'       => 'no',
					'using_composer'         => 'yes',
					'composer_install'       => 'no',
					'using_phpstan'          => 'yes',
					'standalone'             => 'no',
					'remove_project_files'   => 'yes',
					'rollup_composer'        => 'yes',
					'parent_composer_update' => 'no',
					'rollup_phpcs'           => 'yes',
					'remove_git'             => 'yes',
					'delete_configure'       => 'yes',
				]
			),
			$plugin,
		);

		$this->assert_exited_cleanly( $result );

		// Project-level files belong to the parent project.
		$removed = [
			'.deployignore',
			'.editorconfig',
			'.git',
			'.gitattributes',
			'.github',
			'.gitignore',
			'.wp-env.json',
			'CHANGELOG.md',
			'LICENSE',
		];

		foreach ( $removed as $path ) {
			$this->assertFileDoesNotExist( $plugin . '/' . $path );
		}

		// The plugin's dependencies moved to the parent composer.json.
		$this->assertFileDoesNotExist( $plugin . '/composer.json' );
		$this->assertFileDoesNotExist( $plugin . '/composer.lock' );

		$composer = $this->read_json( $parent . '/composer.json' );

		$this->assertArrayHasKey( 'mantle-framework/support', $composer['require'] );
		$this->assertArrayHasKey( 'alleyinteractive/wp-type-extensions', $composer['require'] );
		$this->assertArrayHasKey( 'mantle-framework/testkit', $composer['require-dev'] );
		$this->assertSame( '^1.2', $composer['require']['parent/package'], 'The parent requirements should be kept.' );
		$this->assertTrue( $composer['config']['allow-plugins']['alleyinteractive/composer-wordpress-autoloader'] );
		$this->assertSame( $this->sorted( array_keys( $composer['require'] ) ), array_keys( $composer['require'] ) );

		/*
		 * Known issue: the Composer loader is left in the plugin file, even
		 * though the script reports removing it. Its wrapper comments were
		 * already stripped when Composer was confirmed, so the block can no
		 * longer be matched. Update this when the script is fixed.
		 */
		$this->assertStringContainsString(
			"require_once __DIR__ . '/vendor/wordpress-autoload.php';",
			$this->read( $plugin . '/wp-my-cool-plugin.php' ),
		);

		// The PHPCS configuration inherits from the parent project.
		$phpcs = $this->read( $plugin . '/.phpcs.xml' );

		$this->assertStringContainsString( '<rule ref="../../phpcs.xml" />', $phpcs );
		$this->assertStringContainsString( 'PHP_CodeSniffer standard for My Cool Plugin', $phpcs );
		$this->assertStringContainsString( '<property name="text_domain" type="array" value="wp-my-cool-plugin" />', $phpcs );
		$this->assertStringContainsString( '<property name="prefixes" type="array" value="wp_my_cool_plugin" />', $phpcs );
	}

	/**
	 * Alley plugins should be nudged towards a `wp-` prefixed slug and an
	 * `Alley\WP\` namespace, and the answer should be asked for again when the
	 * suggestion is taken.
	 */
	public function test_warns_when_an_alley_plugin_is_not_prefixed(): void {
		$result = $this->run_configure(
			[
				'author_email'         => 'test@example.com',
				'author_username'      => 'test-user',
				'author_name'          => 'Test Author',
				'vendor_name'          => 'alleyinteractive',
				'plugin_name'          => 'My Cool Plugin',
				'plugin_slug'          => 'my-cool-plugin',
				'plugin_slug_continue' => 'no',
				'plugin_slug_again'    => 'wp-my-cool-plugin',
				'namespace'            => 'My_Cool_Plugin',
				'namespace_continue'   => 'yes',
				'class_name'           => 'My_Cool_Plugin',
				'description'          => 'A very cool plugin.',
				'plugin_file'          => 'wp-my-cool-plugin.php',
				'modify_files'         => 'no',
			],
			$this->install_skeleton(),
		);

		$this->assertStringContainsString( 'Alley WordPress plugin slugs should be prefixed with "wp-"', $result['stdout'] );
		$this->assertStringContainsString( 'Alley WordPress plugins should be prefixed with "Alley\WP\"', $result['stdout'] );

		// The declined slug was asked for again; the namespace was kept as-is.
		$this->assertStringContainsString( 'Plugin      : My Cool Plugin <wp-my-cool-plugin>', $result['stdout'] );
		$this->assertStringContainsString( 'Namespace   : My_Cool_Plugin', $result['stdout'] );
	}

	/**
	 * Invalid namespaces and plugin file names should be rejected.
	 */
	public function test_rejects_an_invalid_namespace_and_plugin_file(): void {
		$result = $this->run_configure(
			[
				'author_email'      => 'test@example.com',
				'author_username'   => 'test-user',
				'author_name'       => 'Test Author',
				'vendor_name'       => 'Test Vendor',
				'plugin_name'       => 'My Cool Plugin',
				'plugin_slug'       => 'wp-my-cool-plugin',
				'namespace'         => 'Not A Namespace!',
				'namespace_again'   => 'Test_Vendor\My_Cool_Plugin',
				'class_name'        => 'My_Cool_Plugin',
				'description'       => 'A very cool plugin.',
				'plugin_file'       => 'not-a-php-file',
				'plugin_file_again' => 'plugin.php',
				'plugin_file_third' => 'wp-my-cool-plugin.php',
				'modify_files'      => 'no',
			],
			$this->install_skeleton(),
		);

		$this->assertStringContainsString( 'Invalid namespace, please try again.', $result['stdout'] );
		$this->assertStringContainsString( 'Invalid plugin file name. Please try again.', $result['stdout'] );
		$this->assertStringContainsString( 'Plugin file already exists. Please try again.', $result['stdout'] );
		$this->assertStringContainsString( 'Namespace   : Test_Vendor\My_Cool_Plugin', $result['stdout'] );
		$this->assertStringContainsString( 'Main File   : wp-my-cool-plugin.php', $result['stdout'] );
	}

	/**
	 * Nothing should be modified when the confirmation is declined.
	 */
	public function test_makes_no_changes_when_the_modification_is_declined(): void {
		$plugin = $this->install_skeleton();
		$before = $this->all_files( $plugin );
		$result = $this->run_configure( $this->base_answers( [ 'modify_files' => 'no' ] ), $plugin );

		$this->assertSame( 1, $result['exit_code'] );

		// The scaffold-only workflow is removed before anything is asked.
		$this->assertSame(
			$this->sorted( array_diff( $before, [ '.github/workflows/merge-develop-to-scaffold.yml' ] ) ),
			$this->all_files( $plugin ),
		);

		$this->assertStringContainsString(
			'Plugin Name: Create WordPress Plugin',
			$this->read( $plugin . '/plugin.php' ),
		);
		$this->assertFileExists( $plugin . '/configure.php' );
		$this->assertFileExists( $plugin . '/tests/ConfigureTest.php' );
	}

	/**
	 * The placeholders the script does not replace today.
	 *
	 * This documents the current behavior rather than endorsing it: the bare
	 * `Create_WordPress_Plugin` token is only replaced as part of the full
	 * `Alley\WP\Create_WordPress_Plugin` namespace, and the `.scaffolder`
	 * directory is excluded from the search and replace entirely. Update this
	 * test when the script is taught to replace them.
	 */
	public function test_documents_the_placeholders_that_are_left_behind(): void {
		$plugin = $this->configure( $this->default_answers() );
		$found  = $this->find_placeholders(
			$plugin,
			[ 'create-wordpress-plugin', 'Create_WordPress_Plugin' ],
			[ 'LICENSE', 'composer.lock' ],
		);

		$this->assertSame(
			[
				'.scaffolder/plugin-feature/config.yml',
				'.scaffolder/plugin-feature/test.php.hbs',
			],
			$found['create-wordpress-plugin'] ?? [],
		);

		$this->assertSame(
			[
				'.scaffolder/plugin-feature/feature.php.hbs',
				'.scaffolder/plugin-feature/test.php.hbs',
				'CLAUDE.md',
				'README.md',
				'scaffold/config.json',
				'src/features/README.md',
			],
			$found['Create_WordPress_Plugin'] ?? [],
		);
	}

	/**
	 * Answers for the prompts that are asked before any file is touched.
	 *
	 * The keys only document which prompt each answer belongs to. Overrides
	 * replace an answer in place; new keys are appended, which is where the
	 * remaining prompts belong.
	 *
	 * @param array<string, string> $overrides Answers to replace or append.
	 * @return array<string, string>
	 */
	private function base_answers( array $overrides = [] ): array {
		return array_merge(
			[
				'author_email'    => 'test@example.com',
				'author_username' => 'test-user',
				'author_name'     => 'Test Author',
				'vendor_name'     => 'Test Vendor',
				'plugin_name'     => 'My Cool Plugin',
				'plugin_slug'     => 'wp-my-cool-plugin',
				'namespace'       => 'Test_Vendor\My_Cool_Plugin',
				'class_name'      => 'My_Cool_Plugin',
				'description'     => 'A very cool plugin.',
				'plugin_file'     => 'wp-my-cool-plugin.php',
				'modify_files'    => 'yes',
			],
			$overrides,
		);
	}

	/**
	 * Answers for a standalone plugin that uses both Node and Composer.
	 *
	 * @param array<string, string> $overrides Answers to replace.
	 * @return array<string, string>
	 */
	private function default_answers( array $overrides = [] ): array {
		return $this->base_answers(
			array_merge(
				[
					'compiling_assets' => 'yes',
					'npm_install'      => 'no',
					'using_composer'   => 'yes',
					'composer_install' => 'no',
					'using_phpstan'    => 'yes',
					'sqlite_testing'   => 'yes',
					'delete_configure' => 'yes',
				],
				$overrides,
			),
		);
	}

	/**
	 * Copy the skeleton, run the configure script and assert that it succeeded.
	 *
	 * @param array<string, string> $answers  Answers to the prompts, in order.
	 * @param string                $relative Directory to install the skeleton in.
	 * @return string The configured plugin directory.
	 */
	private function configure( array $answers, string $relative = 'my-cool-plugin' ): string {
		$plugin = $this->install_skeleton( $relative );

		$this->assert_exited_cleanly( $this->run_configure( $answers, $plugin ) );

		return $plugin;
	}

	/**
	 * Assert that the script ran to completion.
	 *
	 * @param array{exit_code: int, stdout: string, stderr: string} $result Result of the run.
	 */
	private function assert_exited_cleanly( array $result ): void {
		$this->assertSame(
			0,
			$result['exit_code'],
			"The configure script did not exit cleanly:\n" . $result['stdout'] . $result['stderr'],
		);
	}

	/**
	 * Run the configure script against a copy of the skeleton.
	 *
	 * @param array<string, string> $answers Answers to the prompts, in order.
	 * @param string                $plugin  Plugin directory to run in.
	 * @return array{exit_code: int, stdout: string, stderr: string}
	 */
	private function run_configure( array $answers, string $plugin ): array {
		$descriptors = [
			0 => [ 'pipe', 'r' ],
			1 => [ 'pipe', 'w' ],
			2 => [ 'pipe', 'w' ],
		];

		$pipes   = [];
		$process = proc_open(
			[ PHP_BINARY, 'configure.php' ],
			$descriptors,
			$pipes,
			$plugin,
			array_merge( getenv(), [ 'TERM' => 'dumb' ] ),
		);

		$this->assertIsResource( $process, 'Unable to start the configure script.' );

		fwrite( $pipes[0], implode( PHP_EOL, array_values( $answers ) ) . PHP_EOL );
		fclose( $pipes[0] );

		stream_set_blocking( $pipes[1], false );
		stream_set_blocking( $pipes[2], false );

		$stdout    = '';
		$stderr    = '';
		$exit_code = null;
		$deadline  = microtime( true ) + 120;

		while ( true ) {
			$stdout .= (string) stream_get_contents( $pipes[1] );
			$stderr .= (string) stream_get_contents( $pipes[2] );

			$status = proc_get_status( $process );

			if ( ! $status['running'] ) {
				$exit_code = $status['exitcode'];

				break;
			}

			if ( microtime( true ) > $deadline ) {
				proc_terminate( $process, 9 );

				break;
			}

			usleep( 20000 );
		}

		$stdout .= (string) stream_get_contents( $pipes[1] );
		$stderr .= (string) stream_get_contents( $pipes[2] );

		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );

		$this->assertNotNull(
			$exit_code,
			"The configure script timed out, which usually means it asked a question that wasn't answered:\n" . $stdout . $stderr,
		);

		return [
			'exit_code' => $exit_code,
			'stdout'    => $stdout,
			'stderr'    => $stderr,
		];
	}

	/**
	 * Copy the skeleton into the workspace.
	 *
	 * @param string $relative Directory, relative to the workspace.
	 * @return string The absolute path to the copy.
	 */
	private function install_skeleton( string $relative = 'my-cool-plugin' ): string {
		$plugin = $this->workspace . '/' . $relative;

		mkdir( $plugin, 0777, true );

		$this->copy_directory( $this->skeleton_root(), $plugin );

		return $plugin;
	}

	/**
	 * Create a parent project for a plugin to be rolled up into.
	 *
	 * The script looks for a git repository two directories above the plugin
	 * and a `wp-content` directory three directories above it.
	 *
	 * @return string The parent project directory.
	 */
	private function create_parent_project(): string {
		$parent = $this->workspace . '/wp-content';

		mkdir( $parent . '/.git', 0777, true );
		touch( $parent . '/.git/index' );

		file_put_contents(
			$parent . '/composer.json',
			(string) json_encode(
				[
					'name'    => 'parent/project',
					'require' => [ 'parent/package' => '^1.2' ],
					'config'  => [ 'allow-plugins' => [ 'parent/plugin' => true ] ],
				],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
			),
		);

		file_put_contents(
			$parent . '/phpcs.xml',
			'<?xml version="1.0"?>' . PHP_EOL . '<ruleset name="Parent Project" />' . PHP_EOL,
		);

		return $parent;
	}

	/**
	 * The root directory of the skeleton.
	 */
	private function skeleton_root(): string {
		return dirname( __DIR__ );
	}

	/**
	 * Find the files that still contain the given placeholder tokens.
	 *
	 * @param string                  $dir      Directory to search.
	 * @param array<int, string>      $tokens   Tokens to search for.
	 * @param array<int, string>|null $excluded Paths to ignore, defaulting to
	 *                                          the paths the script never touches.
	 * @return array<string, array<int, string>> Matching files, keyed by token.
	 */
	private function find_placeholders( string $dir, array $tokens, ?array $excluded = null ): array {
		$excluded ??= self::UNTOUCHED_PATHS;
		$found      = [];

		foreach ( $this->all_files( $dir ) as $file ) {
			foreach ( $excluded as $exclude ) {
				if ( str_starts_with( $file, $exclude ) ) {
					continue 2;
				}
			}

			$contents = (string) file_get_contents( $dir . '/' . $file );

			foreach ( $tokens as $token ) {
				if ( str_contains( $contents, $token ) ) {
					$found[ $token ][] = $file;
				}
			}
		}

		return $found;
	}

	/**
	 * List every file in a directory, relative to it and sorted.
	 *
	 * @param string $dir Directory to list.
	 * @return array<int, string>
	 */
	private function all_files( string $dir ): array {
		$files = [];

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST,
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$files[] = substr( $file->getPathname(), strlen( $dir ) + 1 );
			}
		}

		return $this->sorted( $files );
	}

	/**
	 * Sort a list of strings, discarding the keys.
	 *
	 * @param array<int|string, string> $values Values to sort.
	 * @return array<int, string>
	 */
	private function sorted( array $values ): array {
		$values = array_values( $values );

		sort( $values );

		return $values;
	}

	/**
	 * Read a file, asserting that it exists.
	 *
	 * @param string $path File to read.
	 */
	private function read( string $path ): string {
		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * Read and decode a JSON file.
	 *
	 * @param string $path File to read.
	 * @return array<string, mixed>
	 */
	private function read_json( string $path ): array {
		$decoded = json_decode( $this->read( $path ), true );

		$this->assertIsArray( $decoded, "{$path} is not valid JSON." );

		return $decoded;
	}

	/**
	 * Copy a directory, skipping the paths that don't belong in a test copy.
	 *
	 * @param string $from Source directory.
	 * @param string $to   Target directory.
	 */
	private function copy_directory( string $from, string $to ): void {
		foreach ( (array) scandir( $from ) as $entry ) {
			if ( '.' === $entry || '..' === $entry || in_array( $entry, self::SKIPPED_PATHS, true ) ) {
				continue;
			}

			$source = $from . '/' . $entry;
			$target = $to . '/' . $entry;

			if ( is_link( $source ) ) {
				continue;
			}

			if ( is_dir( $source ) ) {
				mkdir( $target );

				$this->copy_directory( $source, $target );

				continue;
			}

			copy( $source, $target );
		}
	}

	/**
	 * Recursively delete a directory inside the system temporary directory.
	 *
	 * @param string $path Directory to delete.
	 */
	private function delete_directory( string $path ): void {
		$temporary = (string) realpath( sys_get_temp_dir() );

		if ( $path === $temporary || ! str_starts_with( $path, $temporary . '/' ) ) {
			$this->fail( "Refusing to delete {$path}: it is not inside {$temporary}." );
		}

		if ( ! is_dir( $path ) ) {
			return;
		}

		foreach ( (array) scandir( $path ) as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$child = $path . '/' . $entry;

			if ( is_dir( $child ) && ! is_link( $child ) ) {
				$this->delete_directory( $child );

				continue;
			}

			unlink( $child );
		}

		rmdir( $path );
	}
}
