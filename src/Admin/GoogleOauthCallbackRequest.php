<?php
/**
 * Sanitized Google OAuth callback input.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Reads the provider callback without treating it as a WordPress nonce flow. */
final class GoogleOauthCallbackRequest {
	/**
	 * Read and sanitize the provider callback query arguments.
	 *
	 * @return array{error: string, code: string, state: string}
	 */
	public static function read(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth callback validates provider state transient instead of a WordPress nonce.
		return array(
			'error' => isset( $_GET['error'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['error'] ) ) : '',
			'code'  => isset( $_GET['code'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['code'] ) ) : '',
			'state' => isset( $_GET['state'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['state'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}
