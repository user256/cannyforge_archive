<?php
/**
 * Minimal shim for the WordPress AJAX primitives `ArchiveSearchEndpoint`
 * touches (nonce verification, JSON responses), so its request-handling can
 * be unit tested without a WordPress runtime.
 *
 * Nonce validity is test-controlled (there is no real `wp_verify_nonce`
 * cryptography here — that is a WordPress-runtime concern, out of scope per
 * ticket 601 and covered by the integration tests in ticket 603), via the
 * `cannyforge_test_ajax_referer_valid` global, mirroring the
 * `current_user_can` global in `wp-admin-post-shim.php`. Each function is
 * guarded so a real WordPress environment takes precedence.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

use CannyForge\Archive\Tests\AjaxResponseSpy;

if ( ! function_exists( 'check_ajax_referer' ) ) {
	/**
	 * In-memory check_ajax_referer: honours the test-controlled nonce
	 * validity global and reproduces WordPress's `wp_die()` termination on
	 * failure (via the `wp_die` shim in `wp-admin-post-shim.php`), so the
	 * "invalid nonce" path can be asserted without the real request ever
	 * reaching query-building code.
	 *
	 * @param int|string   $action    Nonce action (ignored; validity is test-controlled).
	 * @param string|false $query_arg Nonce field name (ignored).
	 * @param bool         $stop      Whether to die on failure, matching WordPress's default.
	 * @return int|false
	 * @throws \CannyForge\Archive\Tests\WpDieException When the nonce is invalid and $stop is true.
	 */
	function check_ajax_referer( $action = -1, $query_arg = false, bool $stop = true ) {
		unset( $action, $query_arg );

		if ( (bool) ( $GLOBALS['cannyforge_test_ajax_referer_valid'] ?? true ) ) {
			return 1;
		}

		if ( $stop ) {
			wp_die( '-1' );
		}

		return false;
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	/**
	 * In-memory wp_send_json_success: records the payload instead of echoing
	 * JSON and terminating the process, so a test can assert on it directly.
	 *
	 * @param mixed $data Response data.
	 * @return void
	 */
	function wp_send_json_success( $data = null ): void {
		AjaxResponseSpy::record_success( $data );
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	/**
	 * In-memory wp_send_json_error: records the payload instead of echoing
	 * JSON and terminating the process, so a test can assert on it directly.
	 * Matches the real function's optional status-code argument (ticket 608:
	 * the search throttle sends 429) by delegating to the `status_header`
	 * shim, so a test can assert on the code via `HookSpy`.
	 *
	 * @param mixed    $data        Response data.
	 * @param int|null $status_code Optional HTTP status code to send.
	 * @return void
	 */
	function wp_send_json_error( $data = null, ?int $status_code = null ): void {
		if ( null !== $status_code ) {
			status_header( $status_code );
		}

		AjaxResponseSpy::record_error( $data );
	}
}
