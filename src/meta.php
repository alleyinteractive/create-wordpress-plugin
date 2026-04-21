<?php

declare(strict_types=1);

/**
 * Contains functions for working with meta.
 *
 * @package create-wordpress-plugin
 */
namespace Alley\WP\Create_WordPress_Plugin;

use function Mantle\Support\Helpers\register_meta_from_file;

/**
 * Reads the post meta definitions from config and registers them.
 */
function register_post_meta_from_defs(): void {
	register_meta_from_file( dirname( __DIR__ ) . '/config/post-meta.json', 'post', false );
}

/**
 * Reads the term meta definitions from config and registers them.
 */
function register_term_meta_from_defs(): void {
	register_meta_from_file( dirname( __DIR__ ) . '/config/term-meta.json', 'term', false );
}
