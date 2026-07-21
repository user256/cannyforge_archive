<?php
/**
 * Persisted Google OAuth token state for the archive plugin.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores Google OAuth token state in dedicated option keys.
 *
 * The refresh token is encrypted at rest; the access token is cached with an
 * absolute expiry and a 90-second safety buffer.
 */
final class GoogleTokenStore {
	/**
	 * Connection status: connected.
	 */
	public const STATUS_CONNECTED = 'connected';

	/**
	 * Connection status: disconnected.
	 */
	public const STATUS_DISCONNECTED = 'disconnected';

	/**
	 * Connection status: token refresh failed / expired.
	 */
	public const STATUS_EXPIRED = 'expired';

	/**
	 * Connection status: other connection error.
	 */
	public const STATUS_ERROR = 'error';

	/**
	 * Refresh-token option key.
	 */
	private const REFRESH_TOKEN_KEY = 'cannyforge_archive_google_refresh_token';

	/**
	 * Access-token option key.
	 */
	private const ACCESS_TOKEN_KEY = 'cannyforge_archive_google_access_token';

	/**
	 * Access-token expiry option key.
	 */
	private const ACCESS_TOKEN_EXPIRES_AT_KEY = 'cannyforge_archive_google_token_expires_at';

	/**
	 * Connection-status option key.
	 */
	private const STATUS_KEY = 'cannyforge_archive_google_connection_status';

	/**
	 * Secret cipher for the refresh token.
	 *
	 * @var SecretCipher
	 */
	private SecretCipher $cipher;

	/**
	 * Get-option callable: fn(string $key, mixed $fallback): mixed.
	 *
	 * @var callable
	 */
	private $get_option;

	/**
	 * Set-option callable: fn(string $key, mixed $value): void.
	 *
	 * @var callable
	 */
	private $set_option;

	/**
	 * Construct the token store.
	 *
	 * @param SecretCipher  $cipher     Secret cipher.
	 * @param callable|null $get_option Get-option accessor.
	 * @param callable|null $set_option Set-option accessor.
	 */
	public function __construct(
		?SecretCipher $cipher = null,
		?callable $get_option = null,
		?callable $set_option = null
	) {
		$this->cipher     = $cipher ?? new SecretCipher();
		$this->get_option = $get_option ?? static function ( string $key, $fallback ) {
			return function_exists( 'get_option' ) ? get_option( $key, $fallback ) : $fallback;
		};
		$this->set_option = $set_option ?? static function ( string $key, $value ): void {
			if ( function_exists( 'update_option' ) ) {
				update_option( $key, $value, false );
			}
		};
	}

	/**
	 * The decrypted refresh token, or '' when none is stored.
	 *
	 * @return string
	 */
	public function refresh_token(): string {
		$stored = (string) ( $this->get_option )( self::REFRESH_TOKEN_KEY, '' );

		return '' === $stored ? '' : $this->cipher->decrypt( $stored );
	}

	/**
	 * Persist a refresh token, encrypted at rest.
	 *
	 * @param string $token Refresh token.
	 * @return void
	 */
	public function save_refresh_token( string $token ): void {
		( $this->set_option )( self::REFRESH_TOKEN_KEY, $this->cipher->encrypt( $token ) );
	}

	/**
	 * The cached access token if it still has at least 90 seconds of life.
	 *
	 * @param int $now Current Unix time.
	 * @return string
	 */
	public function valid_access_token( int $now ): string {
		$access  = (string) ( $this->get_option )( self::ACCESS_TOKEN_KEY, '' );
		$expires = (int) ( $this->get_option )( self::ACCESS_TOKEN_EXPIRES_AT_KEY, 0 );

		return ( '' !== $access && $expires > ( $now + 90 ) ) ? $access : '';
	}

	/**
	 * Cache an access token with its absolute expiry.
	 *
	 * @param string $access     Access token.
	 * @param int    $expires_at Absolute Unix expiry.
	 * @return void
	 */
	public function save_access_token( string $access, int $expires_at ): void {
		( $this->set_option )( self::ACCESS_TOKEN_KEY, $access );
		( $this->set_option )( self::ACCESS_TOKEN_EXPIRES_AT_KEY, $expires_at );
	}

	/**
	 * Record the connection status.
	 *
	 * @param string $status Status.
	 * @return void
	 */
	public function set_status( string $status ): void {
		( $this->set_option )( self::STATUS_KEY, $status );
	}

	/**
	 * The current connection status.
	 *
	 * @return string
	 */
	public function status(): string {
		return (string) ( $this->get_option )( self::STATUS_KEY, self::STATUS_DISCONNECTED );
	}

	/**
	 * Clear the stored connection state.
	 *
	 * Keeps the Google client configuration intact, but removes the active token
	 * material and marks the connection disconnected.
	 *
	 * @return void
	 */
	public function clear(): void {
		( $this->set_option )( self::REFRESH_TOKEN_KEY, '' );
		( $this->set_option )( self::ACCESS_TOKEN_KEY, '' );
		( $this->set_option )( self::ACCESS_TOKEN_EXPIRES_AT_KEY, 0 );
		( $this->set_option )( self::STATUS_KEY, self::STATUS_DISCONNECTED );
	}
}
