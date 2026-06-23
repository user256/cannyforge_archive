<?php
/**
 * Google OAuth client for access-token refresh and auth-code exchange.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

/**
 * Hands out valid Google access tokens and stores the connection state.
 *
 * The HTTP POST is injected as a callable so refresh/exchange flows are unit-
 * testable with no WordPress runtime.
 */
final class GoogleOauthClient {
	/**
	 * Google's OAuth token endpoint.
	 */
	private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/**
	 * Token persistence.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $store;

	/**
	 * OAuth client ID.
	 *
	 * @var string
	 */
	private string $client_id;

	/**
	 * OAuth client secret (decrypted in memory).
	 *
	 * @var string
	 */
	private string $client_secret;

	/**
	 * HTTP POST: fn(string $url, array $body): array{code: int, data: array<string, mixed>}|null.
	 *
	 * @var callable
	 */
	private $http;

	/**
	 * The last failure reason.
	 *
	 * @var string
	 */
	private string $last_error = '';

	/**
	 * Construct the client.
	 *
	 * @param GoogleTokenStore $store         Token store.
	 * @param string           $client_id     OAuth client ID.
	 * @param string           $client_secret OAuth client secret.
	 * @param callable|null    $http          HTTP POST transport.
	 */
	public function __construct(
		GoogleTokenStore $store,
		string $client_id,
		string $client_secret,
		?callable $http = null
	) {
		$this->store         = $store;
		$this->client_id     = trim( $client_id );
		$this->client_secret = trim( $client_secret );
		$this->http          = $http ?? $this->default_http();
	}

	/**
	 * Whether a refresh token is stored.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		return '' !== $this->store->refresh_token();
	}

	/**
	 * A valid access token, refreshing if needed.
	 *
	 * Returns '' (and records `last_error`) when the client is not configured or
	 * the refresh flow fails.
	 *
	 * @param int|null $now Current Unix time (defaults to time()).
	 * @return string
	 */
	public function access_token( ?int $now = null ): string {
		$now              = $now ?? time();
		$this->last_error = '';

		$cached = $this->store->valid_access_token( $now );
		if ( '' !== $cached ) {
			return $cached;
		}

		$refresh = $this->store->refresh_token();
		if ( '' === $refresh ) {
			$this->store->set_status( GoogleTokenStore::STATUS_DISCONNECTED );
			return '';
		}

		if ( '' === $this->client_id || '' === $this->client_secret ) {
			$this->last_error = 'Google OAuth client is not configured.';
			$this->store->set_status( GoogleTokenStore::STATUS_ERROR );
			return '';
		}

		return $this->refresh( $refresh, $now );
	}

	/**
	 * Exchange an OAuth authorization code for tokens and persist them.
	 *
	 * @param string   $code         Authorization code.
	 * @param string   $redirect_uri Redirect URI registered with Google.
	 * @param int|null $now          Current Unix time (defaults to time()).
	 * @return bool
	 */
	public function connect( string $code, string $redirect_uri, ?int $now = null ): bool {
		$now              = $now ?? time();
		$this->last_error = '';

		if ( ! $this->can_connect( $code ) ) {
			return false;
		}

		$result = $this->request( $this->connect_request_body( $code, $redirect_uri ) );
		$data   = $this->token_data( $result );
		if ( array() === $data ) {
			return false;
		}

		$access_token = $this->str( $data, 'access_token' );
		if ( '' === $access_token ) {
			$this->last_error = 'Could not complete Google sign-in.';
			$this->store->set_status( GoogleTokenStore::STATUS_ERROR );
			return false;
		}

		$refresh = $this->connect_refresh_token( $data );
		if ( '' === $refresh ) {
			$this->last_error = 'Google did not return a refresh token.';
			$this->store->set_status( GoogleTokenStore::STATUS_ERROR );
			return false;
		}

		$this->persist_connection( $refresh, $access_token, $this->token_ttl( $data ), $now );

		return true;
	}

	/**
	 * Clear the stored connection state.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$this->store->clear();
	}

	/**
	 * The last failure reason.
	 *
	 * @return string
	 */
	public function last_error(): string {
		return $this->last_error;
	}

	/**
	 * Refresh the access token from the stored refresh token.
	 *
	 * @param string $refresh Refresh token.
	 * @param int    $now     Current Unix time.
	 * @return string
	 */
	private function refresh( string $refresh, int $now ): string {
		$result = $this->request(
			array(
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'refresh_token' => $refresh,
				'grant_type'    => 'refresh_token',
			)
		);

		if ( null === $result ) {
			$this->last_error = 'Token request error.';
			$this->store->set_status( GoogleTokenStore::STATUS_ERROR );
			return '';
		}

		$data   = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
		$access = $this->str( $data, 'access_token' );
		if ( '' === $access ) {
			$this->last_error = 'Token refresh failed (HTTP ' . ( $result['code'] ?? 0 ) . ').';
			$this->store->set_status( GoogleTokenStore::STATUS_EXPIRED );
			return '';
		}

		$ttl = isset( $data['expires_in'] ) && is_scalar( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;
		$this->store->save_access_token( $access, $now + $ttl );

		$rotated = $this->str( $data, 'refresh_token' );
		if ( '' !== $rotated ) {
			$this->store->save_refresh_token( $rotated );
		}

		$this->store->set_status( GoogleTokenStore::STATUS_CONNECTED );

		return $access;
	}

	/**
	 * Whether the client is currently in a state where a connect flow can run.
	 *
	 * @param string $code Authorization code.
	 * @return bool
	 */
	private function can_connect( string $code ): bool {
		if ( '' === $code ) {
			$this->last_error = 'Missing authorization code.';
			$this->store->set_status( GoogleTokenStore::STATUS_ERROR );
			return false;
		}

		if ( '' === $this->client_id || '' === $this->client_secret ) {
			$this->last_error = 'Google OAuth client is not configured.';
			$this->store->set_status( GoogleTokenStore::STATUS_ERROR );
			return false;
		}

		return true;
	}

	/**
	 * The token request body for an authorization-code exchange.
	 *
	 * @param string $code         Authorization code.
	 * @param string $redirect_uri Redirect URI.
	 * @return array<string, string>
	 */
	private function connect_request_body( string $code, string $redirect_uri ): array {
		return array(
			'code'          => $code,
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri'  => $redirect_uri,
			'grant_type'    => 'authorization_code',
		);
	}

	/**
	 * Extract a token-response data payload or set the generic request error.
	 *
	 * @param array{code: int, data: array<string, mixed>}|null $result HTTP result.
	 * @return array<string, mixed>
	 */
	private function token_data( ?array $result ): array {
		if ( null !== $result ) {
			return isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
		}

		$this->last_error = 'Token request error.';
		$this->store->set_status( GoogleTokenStore::STATUS_ERROR );

		return array();
	}

	/**
	 * The refresh token to persist after a connect flow.
	 *
	 * Google may omit `refresh_token` on a reconnect; in that case keep the
	 * already stored one if present.
	 *
	 * @param array<string, mixed> $data Token-response data.
	 * @return string
	 */
	private function connect_refresh_token( array $data ): string {
		$refresh = $this->str( $data, 'refresh_token' );

		return '' !== $refresh ? $refresh : $this->store->refresh_token();
	}

	/**
	 * Persist a newly connected Google token state.
	 *
	 * @param string $refresh      Refresh token.
	 * @param string $access_token Access token.
	 * @param int    $ttl          Token TTL in seconds.
	 * @param int    $now          Current Unix time.
	 * @return void
	 */
	private function persist_connection( string $refresh, string $access_token, int $ttl, int $now ): void {
		$this->store->save_refresh_token( $refresh );
		$this->store->save_access_token( $access_token, $now + $ttl );
		$this->store->set_status( GoogleTokenStore::STATUS_CONNECTED );
	}

	/**
	 * Token TTL from a token-response payload.
	 *
	 * @param array<string, mixed> $data Token-response data.
	 * @return int
	 */
	private function token_ttl( array $data ): int {
		return isset( $data['expires_in'] ) && is_scalar( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;
	}

	/**
	 * Post a token request to Google.
	 *
	 * @param array<string, string> $body Request body.
	 * @return array{code: int, data: array<string, mixed>}|null
	 */
	private function request( array $body ): ?array {
		return ( $this->http )( self::TOKEN_URL, $body );
	}

	/**
	 * Read a scalar string from a token-response array.
	 *
	 * @param array<string, mixed> $data Response data.
	 * @param string               $key  Key.
	 * @return string
	 */
	private function str( array $data, string $key ): string {
		return isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? (string) $data[ $key ] : '';
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
			$raw  = function_exists( 'wp_remote_retrieve_body' ) ? (string) wp_remote_retrieve_body( $response ) : '';
			$data = json_decode( $raw, true );

			return array(
				'code' => $code,
				'data' => is_array( $data ) ? $data : array(),
			);
		};
	}
}
