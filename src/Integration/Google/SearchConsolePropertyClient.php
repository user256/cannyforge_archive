<?php
/**
 * Search Console property-list client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists Search Console properties available to the connected Google account.
 */
final class SearchConsolePropertyClient {
	/**
	 * Search Console sites endpoint.
	 */
	private const SITES_URL = 'https://www.googleapis.com/webmasters/v3/sites';

	/**
	 * Google OAuth client.
	 *
	 * @var GoogleOauthClient
	 */
	private GoogleOauthClient $oauth;

	/**
	 * HTTP GET transport.
	 *
	 * @var callable
	 */
	private $http;

	/**
	 * Last actionable failure.
	 *
	 * @var string
	 */
	private string $last_error = '';

	/**
	 * Construct the client.
	 *
	 * @param GoogleOauthClient $oauth Google OAuth client.
	 * @param callable|null     $http  HTTP GET transport.
	 */
	public function __construct( GoogleOauthClient $oauth, ?callable $http = null ) {
		$this->oauth = $oauth;
		$this->http  = $http ?? $this->default_http();
	}

	/**
	 * Fetch the properties visible to the connected account.
	 *
	 * @return array<int, array{site_url: string, permission: string}>
	 */
	public function list_properties(): array {
		$this->last_error = '';
		$access_token     = $this->oauth->access_token();

		if ( '' === $access_token ) {
			$this->last_error = '' !== $this->oauth->last_error()
				? $this->oauth->last_error()
				: __( 'Google is not connected.', 'cannyforge-archive' );
			return array();
		}

		$result = ( $this->http )( self::SITES_URL, $access_token );
		if ( null === $result ) {
			$this->last_error = __( 'The Search Console properties request could not be completed.', 'cannyforge-archive' );
			return array();
		}

		$code = (int) ( $result['code'] ?? 0 );
		if ( $code < 200 || $code >= 300 ) {
			$this->last_error = sprintf(
				/* translators: %d: HTTP response status code. */
				__( 'Google returned HTTP %d while listing Search Console properties.', 'cannyforge-archive' ),
				$code
			);
			return array();
		}

		return $this->normalise_properties( $result );
	}

	/**
	 * Convert the API response into the small shape used by the admin UI.
	 *
	 * @param array<string, mixed> $result HTTP result.
	 * @return array<int, array{site_url: string, permission: string}>
	 */
	private function normalise_properties( array $result ): array {
		$data  = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
		$sites = isset( $data['siteEntry'] ) && is_array( $data['siteEntry'] ) ? $data['siteEntry'] : array();
		$clean = array();

		foreach ( $sites as $site ) {
			if ( ! is_array( $site ) ) {
				continue;
			}

			$property = $this->normalise_property( $site );
			if ( null !== $property ) {
				$clean[] = $property;
			}
		}

		usort(
			$clean,
			static fn ( array $left, array $right ): int => strnatcasecmp( $left['site_url'], $right['site_url'] )
		);

		return $clean;
	}

	/**
	 * Convert one API site entry.
	 *
	 * @param array<string, mixed> $site API site entry.
	 * @return array{site_url: string, permission: string}|null
	 */
	private function normalise_property( array $site ): ?array {
		if ( ! is_string( $site['siteUrl'] ?? null ) || '' === trim( $site['siteUrl'] ) ) {
			return null;
		}

		return array(
			'site_url'   => trim( $site['siteUrl'] ),
			'permission' => is_string( $site['permissionLevel'] ?? null ) ? trim( $site['permissionLevel'] ) : '',
		);
	}

	/**
	 * Last failure message.
	 *
	 * @return string
	 */
	public function last_error(): string {
		return $this->last_error;
	}

	/**
	 * Default WordPress HTTP GET transport.
	 *
	 * @return callable
	 */
	private function default_http(): callable {
		return static function ( string $url, string $access_token ): ?array {
			if ( ! function_exists( 'wp_remote_get' ) ) {
				return null;
			}

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 20,
					'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
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
