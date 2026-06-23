<?php
/**
 * Search Console report client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Queries Search Console for top page rows.
 *
 * HTTP is injected so report fetching and request-body shaping can be tested
 * without a live Google account.
 */
final class SearchConsoleClient {
	/**
	 * Search Console query endpoint prefix.
	 */
	private const QUERY_URL_PREFIX = 'https://www.googleapis.com/webmasters/v3/sites/';

	/**
	 * Google OAuth client.
	 *
	 * @var GoogleOauthClient
	 */
	private GoogleOauthClient $oauth;

	/**
	 * HTTP POST: fn(string $url, string $access_token, array $body): array{code: int, data: array<string, mixed>}|null.
	 *
	 * @var callable
	 */
	private $http;

	/**
	 * Today-provider: fn(): string in `Y-m-d`.
	 *
	 * @var callable
	 */
	private $today;

	/**
	 * Construct the client.
	 *
	 * @param GoogleOauthClient $oauth Google OAuth client.
	 * @param callable|null     $http  HTTP POST transport.
	 * @param callable|null     $today Today-provider.
	 */
	public function __construct( GoogleOauthClient $oauth, ?callable $http = null, ?callable $today = null ) {
		$this->oauth = $oauth;
		$this->http  = $http ?? $this->default_http();
		$this->today = $today ?? static function (): string {
			return gmdate( 'Y-m-d' );
		};
	}

	/**
	 * Query Search Console top-page rows for the configured site and date window.
	 *
	 * @param string $site_url Search Console site property identifier.
	 * @param int    $days     Report window in days.
	 * @param int    $limit    Maximum rows to request.
	 * @return array<int, array<string, mixed>>
	 */
	public function query_top_pages( string $site_url, int $days, int $limit ): array {
		if ( $limit < 1 || '' === trim( $site_url ) ) {
			return array();
		}

		$access_token = $this->oauth->access_token();
		if ( '' === $access_token ) {
			return array();
		}

		$result = ( $this->http )(
			$this->query_url( $site_url ),
			$access_token,
			$this->build_request_body( $days, ( $this->today )(), $limit )
		);

		if ( null === $result ) {
			return array();
		}

		$data = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
		$rows = isset( $data['rows'] ) && is_array( $data['rows'] ) ? $data['rows'] : array();

		return array_values( array_filter( $rows, 'is_array' ) );
	}

	/**
	 * Build the Search Console query request body.
	 *
	 * The date window is inclusive: `$days = 1` means today only.
	 *
	 * @param int    $days  Report window in days.
	 * @param string $today Today's date in `Y-m-d`.
	 * @param int    $limit Maximum rows to request.
	 * @return array<string, mixed>
	 */
	public function build_request_body( int $days, string $today, int $limit ): array {
		$range = $this->date_range( $days, $today );

		return array(
			'startDate'  => $range['startDate'],
			'endDate'    => $range['endDate'],
			'dimensions' => array( 'page' ),
			'rowLimit'   => max( 1, $limit ),
		);
	}

	/**
	 * Extract clean page URLs from Search Console rows, preserving order.
	 *
	 * @param array<int, array<string, mixed>> $rows  Raw Search Console rows.
	 * @param int                              $limit Maximum URLs to return.
	 * @return string[]
	 */
	public function extract_page_urls( array $rows, int $limit ): array {
		$pages = array();

		foreach ( $rows as $row ) {
			if ( ! isset( $row['keys'] ) || ! is_array( $row['keys'] ) ) {
				continue;
			}

			$page = $row['keys'][0] ?? '';
			if ( ! is_string( $page ) || '' === trim( $page ) ) {
				continue;
			}

			$clean = trim( $page );
			if ( ! in_array( $clean, $pages, true ) ) {
				$pages[] = $clean;
			}

			if ( count( $pages ) >= $limit ) {
				break;
			}
		}

		return $pages;
	}

	/**
	 * The request URL for a Search Console property.
	 *
	 * @param string $site_url Search Console site property identifier.
	 * @return string
	 */
	private function query_url( string $site_url ): string {
		return self::QUERY_URL_PREFIX . rawurlencode( trim( $site_url ) ) . '/searchAnalytics/query';
	}

	/**
	 * Inclusive date range for the Search Console query.
	 *
	 * @param int    $days  Report window in days.
	 * @param string $today Today's date in `Y-m-d`.
	 * @return array{startDate: string, endDate: string}
	 */
	private function date_range( int $days, string $today ): array {
		$end   = new DateTimeImmutable( $today, new DateTimeZone( 'UTC' ) );
		$start = $end->modify( '-' . max( 0, $days - 1 ) . ' days' );

		return array(
			'startDate' => $start->format( 'Y-m-d' ),
			'endDate'   => $end->format( 'Y-m-d' ),
		);
	}

	/**
	 * The default WordPress HTTP POST transport.
	 *
	 * @return callable
	 */
	private function default_http(): callable {
		return static function ( string $url, string $access_token, array $body ): ?array {
			if ( ! function_exists( 'wp_remote_post' ) ) {
				return null;
			}

			$encoded = wp_json_encode( $body );
			if ( false === $encoded ) {
				return null;
			}

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 20,
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => $encoded,
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
