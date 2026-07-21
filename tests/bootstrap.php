<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads the Composer autoloader and a minimal in-memory shim for the handful
 * of WordPress option functions the engine touches, so the unit suite runs
 * without a full WordPress runtime.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/wp-options-shim.php';
require __DIR__ . '/wp-transients-shim.php';
require __DIR__ . '/wp-view-shim.php';
require __DIR__ . '/wp-hooks-shim.php';
require __DIR__ . '/wp-admin-post-shim.php';
require __DIR__ . '/wp-admin-redirect-shim.php';
require __DIR__ . '/wp-ajax-shim.php';
require __DIR__ . '/wp-multisite-shim.php';
require __DIR__ . '/wp-wpdb-shim.php';
