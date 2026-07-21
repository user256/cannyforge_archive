<?php
/**
 * Minimal in-memory shim for the WordPress multisite primitives
 * `uninstall.php` uses to iterate every site in a network (ticket 606).
 *
 * Controlled via the `cannyforge_test_is_multisite` / `cannyforge_test_site_ids`
 * globals so tests can toggle single-site vs. multisite behaviour. Guarded so
 * a real WordPress environment takes precedence.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! function_exists( 'is_multisite' ) ) {
	/**
	 * In-memory is_multisite (override via `cannyforge_test_is_multisite`).
	 *
	 * @return bool
	 */
	function is_multisite(): bool {
		return (bool) ( $GLOBALS['cannyforge_test_is_multisite'] ?? false );
	}
}

if ( ! function_exists( 'get_sites' ) ) {
	/**
	 * In-memory get_sites: returns the configured site ID list (override via
	 * `cannyforge_test_site_ids`), ignoring the query args.
	 *
	 * @param array<string, mixed> $args Query args (ignored in the shim).
	 * @return int[]
	 */
	function get_sites( array $args = array() ) {
		unset( $args );
		return $GLOBALS['cannyforge_test_site_ids'] ?? array();
	}
}

if ( ! function_exists( 'switch_to_blog' ) ) {
	/**
	 * In-memory switch_to_blog: records the current blog ID, and every blog
	 * ID switched to, for inspection.
	 *
	 * @param int $blog_id Site ID to switch to.
	 * @return bool
	 */
	function switch_to_blog( int $blog_id ): bool {
		$GLOBALS['cannyforge_test_current_blog_id']        = $blog_id;
		$GLOBALS['cannyforge_test_switch_to_blog_calls'][] = $blog_id;
		return true;
	}
}

if ( ! function_exists( 'restore_current_blog' ) ) {
	/**
	 * In-memory restore_current_blog: clears the recorded current blog ID and
	 * counts the call for inspection.
	 *
	 * @return bool
	 */
	function restore_current_blog(): bool {
		unset( $GLOBALS['cannyforge_test_current_blog_id'] );
		$GLOBALS['cannyforge_test_restore_current_blog_calls'] = 1 + ( $GLOBALS['cannyforge_test_restore_current_blog_calls'] ?? 0 );
		return true;
	}
}
