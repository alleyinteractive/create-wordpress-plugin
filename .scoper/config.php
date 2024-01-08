<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// Raise the memory limit.
ini_set('memory_limit', '512M');

$baseDir = dirname(__DIR__);

if (!is_dir($baseDir.'/vendor')) {
	throw new RuntimeException('Unable to find the vendor directory, have you executed composer install?');
}

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

// Example of collecting files to include in the scoped build but to not scope
// leveraging the isolated finder.
$excludedFiles = array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathName(),
    iterator_to_array(
        Finder::create()->files()->in(__DIR__),
        false,
    ),
);

function getWpExcludedSymbols(string $fileName): array
{
    $filePath = dirname(__DIR__).'/vendor/sniccowp/php-scoper-wordpress-excludes/generated/'.$fileName;

    return json_decode(
        file_get_contents($filePath),
        true,
    );
}

$wp_classes   = getWpExcludedSymbols('exclude-wordpress-classes.json');
$wp_functions = getWpExcludedSymbols('exclude-wordpress-functions.json');
$wp_constants = getWpExcludedSymbols('exclude-wordpress-constants.json');

return [
    // The prefix configuration. If a non-null value is used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => 'Alley\\WP\\Create_WordPress_Plugin',

    // The base output directory for the prefixed files.
    // This will be overridden by the 'output-dir' command line option if present.
    'output-dir' => $baseDir.'/.scoper/build',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
		// Include vendor files.
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'tests',
				'.scoper',
            ])
            ->in($baseDir.'/vendor'),
		// Include all plugin files.
		Finder::create()->files()
			->in($baseDir)
			->exclude([
				'build',
				'vendor',
				'tests',
				'node_modules',
			]),
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
        // 'src/an-excluded-file.php',
        ...$excludedFiles,
    ],

	'exclude-classes' => $wp_classes,
	'exclude-constants' => $wp_functions,
	'exclude-functions' => $wp_constants,
];
