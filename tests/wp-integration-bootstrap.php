<?php
/**
 * Bootstrap for the real-WordPress integration suite (ticket 603).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

/**
 * Unlike tests/bootstrap.php (which shims a handful of WordPress functions so
 * the unit suite runs without WordPress), this suite drives a disposable,
 * fully real WordPress instance booted by wp-env over HTTP and WP-CLI — see
 * scripts/run-integration-tests.sh. No shims are loaded here.
 *
 * `ABSPATH` is still defined so the plugin's own `src/` classes — each
 * guarded by `if ( ! defined( 'ABSPATH' ) ) exit;` — can be autoloaded for
 * their public constants (endpoint slugs, AJAX action/nonce names) without
 * that guard short-circuiting the process.
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require __DIR__ . '/../vendor/autoload.php';
