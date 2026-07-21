<?php
/**
 * Minimal shim for the WordPress admin-post primitives the Google connection
 * controller touches (capability checks, nonces, query-arg building, redirects,
 * wp_die), so its scope-selection, CSRF-state, and revocation behaviour can be
 * unit tested without a full WordPress runtime.
 *
 * This is deliberately scoped to ticket 614's controller behaviour, not a
 * general admin-post test harness — see ticket 602 for that. wp_die/wp_redirect
 * throw instead of terminating the process, so a test can assert on the
 * outcome instead of the process exiting mid-test. Each is guarded so a real
 * WordPress environment takes precedence. wp_safe_redirect() is defined
 * separately, in wp-admin-redirect-shim.php, scoped to the
 * CannyForge\Archive\Admin namespace — see that file for why.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

use CannyForge\Archive\Tests\WpDieException;
use CannyForge\Archive\Tests\WpRedirectException;

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * In-memory current_user_can, defaulting to true (override via the
	 * `cannyforge_test_current_user_can` global for capability-check tests).
	 *
	 * @param string $capability Capability.
	 * @return bool
	 */
	function current_user_can( string $capability ): bool {
		unset( $capability );
		return (bool) ( $GLOBALS['cannyforge_test_current_user_can'] ?? true );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	/**
	 * In-memory get_current_user_id (override via the
	 * `cannyforge_test_current_user_id` global).
	 *
	 * @return int
	 */
	function get_current_user_id(): int {
		return (int) ( $GLOBALS['cannyforge_test_current_user_id'] ?? 1 );
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	/**
	 * In-memory check_admin_referer: always accepts in the test runtime, since
	 * nonce verification is a WordPress-runtime concern outside this ticket's
	 * scope (the CSRF surface this ticket covers is the OAuth state transient).
	 *
	 * @param int|string $action    Nonce action.
	 * @param string     $query_arg Nonce field name.
	 * @return true
	 */
	function check_admin_referer( $action = -1, string $query_arg = '_wpnonce' ) {
		unset( $action, $query_arg );
		return true;
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	/**
	 * Deterministic in-memory wp_generate_password.
	 *
	 * @param int  $length              Password length.
	 * @param bool $special_chars       Include standard special characters (ignored).
	 * @param bool $extra_special_chars Include extra special characters (ignored).
	 * @return string
	 */
	function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string {
		unset( $special_chars, $extra_special_chars );
		return substr( str_repeat( 'a', max( 1, $length ) ), 0, $length );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Pass-through sanitize_text_field (trims only).
	 *
	 * @param string $str Raw string.
	 * @return string
	 */
	function sanitize_text_field( string $str ): string {
		return trim( $str );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Pass-through wp_unslash (no slashing happens in the test runtime).
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * In-memory add_query_arg supporting the `add_query_arg( array $args, string $url )`
	 * form this codebase uses.
	 *
	 * @param array<string, mixed> $args Query args to append.
	 * @param string               $url  Base URL.
	 * @return string
	 */
	function add_query_arg( array $args, string $url ): string {
		$separator = ( false === strpos( $url, '?' ) ) ? '?' : '&';
		return $url . $separator . http_build_query( $args );
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	/**
	 * In-memory wp_die: throws instead of terminating the process.
	 *
	 * @param string $message Death message.
	 * @return never
	 * @throws WpDieException Always, in place of a real fatal exit.
	 */
	function wp_die( string $message = '' ): never {
		throw new WpDieException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- test-only control-flow signal, never rendered as output.
	}
}

if ( ! function_exists( 'wp_redirect' ) ) {
	/**
	 * In-memory wp_redirect: throws instead of sending headers + exiting.
	 *
	 * @param string $location Redirect target.
	 * @param int    $status   HTTP status (ignored).
	 * @return never
	 * @throws WpRedirectException Always, in place of a real redirect + exit.
	 */
	function wp_redirect( string $location, int $status = 302 ): never {
		unset( $status );
		throw new WpRedirectException( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- test-only control-flow signal, never rendered as output.
	}
}
