<?php

/**
 * Autoload the wordpress-autoload.php file from the vendor directory.
 *
 * Used to automatically load the file when vendor/autoload.php is loaded.
 */

$autoloadFiles = [
    preg_replace('#/vendor/.*$#', '/vendor/wordpress-autoload.php', __DIR__),
    '../../../vendor/wordpress-autoload.php',
    '../../vendor/wordpress-autoload.php',
];

foreach ($autoloadFiles as $path) {
    if ($path && !is_dir($path) && file_exists($path)) {
        require_once $path;
        break;
    }
}
