<?php
/**
 * Best-effort Google token revocation, shared by disconnect and uninstall.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Revokes the stored Google grant with Google before clearing local state.
 *
 * Ticket 614: disconnecting locally without revoking leaves a stale grant in
 * the user's Google account. This service is the single place that talks to
 * Google's revocation endpoint so both the admin "Disconnect" action and the
 * plugin uninstall routine (ticket 606) share one network/token code path
 * instead of duplicating it.
 *
 * Revocation is always best-effort: local token state is cleared whether or
 * not the remote call succeeds, so a stuck/unreachable Google endpoint never
 * prevents an admin from disconnecting. Callers should surface
 * {@see self::last_error()} to the user when the remote call failed, without
 * blocking the local cleanup that already happened.
 */
final class GoogleRevocationService {
	/**
	 * Google's OAuth token revocation endpoint.
	 */
	private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

	/**
	 * Token persistence.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $store;

	/**
	 * HTTP POST: fn(string $url, array $body): array{code: int}|null.
	 *
	 * @var callable
	 */
	private $http;

	/**
	 * The last failure reason from a remote revocation attempt.
	 *
	 * @var string
	 */
	private string $last_error = '';

	/**
	 * Construct the service.
	 *
	 * @param GoogleTokenStore $store Token store.
	 * @param callable|null    $http  HTTP POST transport.
	 */
	public function __construct( GoogleTokenStore $store, ?callable $http = null ) {
		$this->store = $store;
		$this->http  = $http ?? $this->default_http();
	}

	/**
	 * Revoke the stored token with Google (if any), then clear local state.
	 *
	 * Idempotent: calling this with no stored tokens (already disconnected)
	 * does nothing remotely and reports success, and local cleanup always
	 * runs regardless of the remote outcome.
	 *
	 * @param int|null $now Current Unix time (defaults to time()), used to
	 *                      judge whether a cached access token is still valid
	 *                      when no refresh token is stored.
	 * @return bool Whether the remote revocation succeeded (or nothing needed
	 *              revoking). False means local state is still cleared, but
	 *              the caller should tell the admin Google's grant may linger.
	 */
	public function revoke_and_clear( ?int $now = null ): bool {
		$revoked = $this->revoke_remote( $now ?? time() );
		$this->store->clear();

		return $revoked;
	}

	/**
	 * The last remote-revocation failure reason, or '' when the last attempt
	 * succeeded (or nothing needed revoking).
	 *
	 * @return string
	 */
	public function last_error(): string {
		return $this->last_error;
	}

	/**
	 * Attempt to revoke whichever token is currently stored.
	 *
	 * Google's revoke endpoint accepts either an access token or a refresh
	 * token; the refresh token is preferred because it also invalidates the
	 * associated access tokens Google can see.
	 *
	 * @param int $now Current Unix time.
	 * @return bool
	 */
	private function revoke_remote( int $now ): bool {
		$this->last_error = '';

		$token = $this->token_to_revoke( $now );
		if ( '' === $token ) {
			return true;
		}

		$result = ( $this->http )( self::REVOKE_URL, array( 'token' => $token ) );
		if ( null === $result ) {
			$this->last_error = 'Could not reach Google to revoke access.';
			return false;
		}

		$code = isset( $result['code'] ) ? (int) $result['code'] : 0;
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		// Google answers with 400 invalid_token when the token is already
		// expired/revoked; treat that as an idempotent success rather than a
		// failure the admin needs to act on.
		if ( 400 === $code ) {
			return true;
		}

		$this->last_error = 'Google revocation failed (HTTP ' . $code . ').';
		return false;
	}

	/**
	 * The token to send to Google's revoke endpoint, preferring the refresh
	 * token and falling back to a still-valid cached access token.
	 *
	 * @param int $now Current Unix time.
	 * @return string
	 */
	private function token_to_revoke( int $now ): string {
		$refresh = $this->store->refresh_token();
		if ( '' !== $refresh ) {
			return $refresh;
		}

		return $this->store->valid_access_token( $now );
	}

	/**
	 * The default WordPress HTTP POST transport.
	 *
	 * @return callable
	 */
	private function default_http(): callable {
		return static function ( string $url, array $body ): ?array {
			if ( ! function_exists( 'wp_remote_post' ) ) {
				return null;
			}

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 20,
					'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					'body'    => $body,
				)
			);

			if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
				return null;
			}

			$code = function_exists( 'wp_remote_retrieve_response_code' ) ? (int) wp_remote_retrieve_response_code( $response ) : 0;

			return array( 'code' => $code );
		};
	}
}
