<?php
/**
 * Rector Configuration
 *
 * @link https://getrector.com/documentation
 * @package create-wordpress-plugin
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withParallel()
	->withIndent(
		indentChar: '	',
		indentSize: 1,
	)
	->withRootFiles()
	->withPaths( [
		__DIR__ . '/src',
		__DIR__ . '/tests',
	] )
	/**
	 * --------------------------------------------------------------------------
	 * Enabled rector rules/rulesets.
	 * --------------------------------------------------------------------------
	 *
	 * @link https://getrector.com/find-rule
	 */
	->withPreparedSets(
		codeQuality: true,
		deadCode: true,
		earlyReturn: true,
		typeDeclarations: true,
	)
	/**
	 * --------------------------------------------------------------------------
	 * Enable Rector to keep your code up-to-date with the latest features from the PHP version in your composer.json file
	 * --------------------------------------------------------------------------
	 */
	->withPhpSets()
	/**
	 * --------------------------------------------------------------------------
	 * PHPUnit rules, matched to the PHPUnit version in composer.json.
	 * --------------------------------------------------------------------------
	 */
	->withComposerBased( phpunit: true )
	->withAttributesSets( phpunit: true )
	/**
	 * --------------------------------------------------------------------------
	 * Rector rules to skip.
	 * --------------------------------------------------------------------------
	 *
	 * @link https://getrector.com/documentation/ignoring-rules-or-paths
	 */
	->withSkip( [] );
