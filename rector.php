<?php
/**
 * Rector Configuration
 *
 * @link https://getrector.com/documentation
 * @package create-wordpress-plugin
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

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
	->withSets( [
		PHPUnitSetList::PHPUNIT_100,
		PHPUnitSetList::PHPUNIT_110,
		PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
	] )
	/**
	 * --------------------------------------------------------------------------
	 * Rector rules to skip.
	 * --------------------------------------------------------------------------
	 *
	 * @link https://getrector.com/documentation/ignoring-rules-or-paths
	 */
	->withSkip( [
		Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector::class,
	] );
