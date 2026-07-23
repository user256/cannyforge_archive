<?php
/**
 * Google Analytics property-list client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists the GA4 properties visible to the connected Google account.
 */
final class Ga4PropertyClient {
	/**
	 * Analytics Admin account summaries endpoint.
	 */
	private const SUMMARIES_URL = 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries';

	/**
	 * Google OAuth client.
	 *
	 * @var GoogleOauthClient
	 */
	private GoogleOauthClient $oauth;

	/**
	 * HTTP GET transport: fn(string $url, string $access_token): ?array.
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
	 * Fetch the GA4 properties visible to the connected account.
	 *
	 * @return array<int, array{property_id: string, display_name: string, account_name: string}>
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

		$properties = array();
		$page_token = '';
		for ( $page = 0; $page < 10; $page++ ) {
			$result = $this->fetch_page( $access_token, $page_token );
			if ( null === $result ) {
				$this->last_error = __( 'The GA4 properties request could not be completed.', 'cannyforge-archive' );
				return array();
			}

			$raw_code = $result['code'] ?? 0;
			$code     = is_int( $raw_code ) ? $raw_code : ( is_numeric( $raw_code ) ? (int) $raw_code : 0 );
			if ( $code < 200 || $code >= 300 ) {
				$this->last_error = sprintf(
					/* translators: %d: HTTP response status code. */
					__( 'Google returned HTTP %d while listing GA4 properties.', 'cannyforge-archive' ),
					$code
				);
				return array();
			}

			$properties = array_merge( $properties, $this->normalise_summaries( $result ) );
			$page_token = $this->next_page_token( $result );
			if ( '' === $page_token ) {
				break;
			}
		}

		usort(
			$properties,
			static fn ( array $left, array $right ): int => strnatcasecmp(
				$left['display_name'] . $left['property_id'],
				$right['display_name'] . $right['property_id']
			)
		);

		return $properties;
	}

	/**
	 * Fetch one account-summary page.
	 *
	 * @param string $access_token Access token.
	 * @param string $page_token   Page token, or empty for the first page.
	 * @return array<string, mixed>|null HTTP result.
	 */
	private function fetch_page( string $access_token, string $page_token ): ?array {
		$url = add_query_arg( array( 'pageSize' => 200 ), self::SUMMARIES_URL );
		if ( '' !== $page_token ) {
			$url = add_query_arg( array( 'pageToken' => $page_token ), $url );
		}

		return ( $this->http )( $url, $access_token );
	}

	/**
	 * Read the next page token from an HTTP result.
	 *
	 * @param array<string, mixed> $result HTTP result.
	 * @return string
	 */
	private function next_page_token( array $result ): string {
		$data = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();

		return is_string( $data['nextPageToken'] ?? null ) ? trim( $data['nextPageToken'] ) : '';
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
	 * Normalise account summaries into the small shape used by the admin UI.
	 *
	 * @param array<string, mixed> $result HTTP result.
	 * @return array<int, array{property_id: string, display_name: string, account_name: string}>
	 */
	private function normalise_summaries( array $result ): array {
		$data       = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
		$accounts   = isset( $data['accountSummaries'] ) && is_array( $data['accountSummaries'] ) ? $data['accountSummaries'] : array();
		$properties = array();

		foreach ( $accounts as $account ) {
			if ( ! is_array( $account ) ) {
				continue;
			}
			$properties = array_merge( $properties, $this->normalise_account( $account ) );
		}

		return $properties;
	}

	/**
	 * Normalise one Analytics account summary.
	 *
	 * @param array<string, mixed> $account Account summary.
	 * @return array<int, array{property_id: string, display_name: string, account_name: string}>
	 */
	private function normalise_account( array $account ): array {
		$account_name   = is_string( $account['displayName'] ?? null ) ? trim( $account['displayName'] ) : '';
		$raw_properties = isset( $account['propertySummaries'] ) && is_array( $account['propertySummaries'] ) ? $account['propertySummaries'] : array();
		$properties     = array();

		foreach ( $raw_properties as $property ) {
			$normalised = is_array( $property ) ? $this->normalise_property( $property, $account_name ) : null;
			if ( null !== $normalised ) {
				$properties[] = $normalised;
			}
		}

		return $properties;
	}

	/**
	 * Normalise one Analytics property summary.
	 *
	 * @param array<string, mixed> $property    Property summary.
	 * @param string               $account_name Account display name.
	 * @return array{property_id: string, display_name: string, account_name: string}|null
	 */
	private function normalise_property( array $property, string $account_name ): ?array {
		if ( ! is_string( $property['property'] ?? null ) ) {
			return null;
		}
		if ( ! preg_match( '#^properties/(\d+)$#', trim( $property['property'] ), $matches ) ) {
			return null;
		}

		return array(
			'property_id'  => $matches[1],
			'display_name' => is_string( $property['displayName'] ?? null ) ? trim( $property['displayName'] ) : '',
			'account_name' => $account_name,
		);
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
