<?php
/**
 * GA4 Data API report client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DateTimeImmutable;
use DateTimeZone;

/**
 * Queries the GA4 Data API (`runReport`) for top page rows.
 *
 * HTTP is injected so report fetching and request-body shaping can be tested
 * without a live Google account, mirroring {@see SearchConsoleClient}. GA4 is an
 * optional second Google signal (ticket 406); it never replaces the Search
 * Console-first path.
 */
final class Ga4Client {
	/**
	 * GA4 Data API report endpoint prefix.
	 */
	private const QUERY_URL_PREFIX = 'https://analyticsdata.googleapis.com/v1beta/properties/';

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
	 * Query GA4 top-page rows for the configured property and date window.
	 *
	 * @param string $property_id GA4 property ID (digits only).
	 * @param int    $days        Report window in days.
	 * @param int    $limit       Maximum rows to request.
	 * @return array<int, array<string, mixed>>
	 */
	public function query_top_pages( string $property_id, int $days, int $limit ): array {
		if ( $limit < 1 || '' === trim( $property_id ) ) {
			return array();
		}

		$access_token = $this->oauth->access_token();
		if ( '' === $access_token ) {
			return array();
		}

		$result = ( $this->http )(
			$this->query_url( $property_id ),
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
	 * Build the GA4 `runReport` request body.
	 *
	 * Reports `screenPageViews` against the `pagePath` dimension, ordered by
	 * views descending. The date window is inclusive: `$days = 1` means today
	 * only.
	 *
	 * @param int    $days  Report window in days.
	 * @param string $today Today's date in `Y-m-d`.
	 * @param int    $limit Maximum rows to request.
	 * @return array<string, mixed>
	 */
	public function build_request_body( int $days, string $today, int $limit ): array {
		$range = $this->date_range( $days, $today );

		return array(
			'dateRanges' => array(
				array(
					'startDate' => $range['startDate'],
					'endDate'   => $range['endDate'],
				),
			),
			'dimensions' => array(
				array( 'name' => 'pagePath' ),
			),
			'metrics'    => array(
				array( 'name' => 'screenPageViews' ),
			),
			'orderBys'   => array(
				array(
					'desc'   => true,
					'metric' => array( 'metricName' => 'screenPageViews' ),
				),
			),
			'limit'      => (string) max( 1, $limit ),
		);
	}

	/**
	 * Extract clean page paths from GA4 rows, preserving order.
	 *
	 * GA4 returns the dimension value as a site-relative path (e.g.
	 * `/2024/my-post/`), unlike Search Console's absolute URLs. The mapping to
	 * post IDs is handled downstream via `url_to_postid`, which accepts paths.
	 *
	 * @param array<int, array<string, mixed>> $rows  Raw GA4 rows.
	 * @param int                              $limit Maximum paths to return.
	 * @return string[]
	 */
	public function extract_page_urls( array $rows, int $limit ): array {
		$pages = array();

		foreach ( $rows as $row ) {
			$path = $this->row_path( $row );
			if ( '' === $path ) {
				continue;
			}

			if ( ! in_array( $path, $pages, true ) ) {
				$pages[] = $path;
			}

			if ( count( $pages ) >= $limit ) {
				break;
			}
		}

		return $pages;
	}

	/**
	 * The first dimension value (page path) from a GA4 row, trimmed.
	 *
	 * @param array<string, mixed> $row Raw GA4 row.
	 * @return string
	 */
	private function row_path( array $row ): string {
		if ( ! isset( $row['dimensionValues'] ) || ! is_array( $row['dimensionValues'] ) ) {
			return '';
		}

		$first = $row['dimensionValues'][0] ?? null;
		if ( ! is_array( $first ) || ! isset( $first['value'] ) || ! is_string( $first['value'] ) ) {
			return '';
		}

		return trim( $first['value'] );
	}

	/**
	 * The request URL for a GA4 property's `runReport` call.
	 *
	 * @param string $property_id GA4 property ID.
	 * @return string
	 */
	private function query_url( string $property_id ): string {
		return self::QUERY_URL_PREFIX . rawurlencode( trim( $property_id ) ) . ':runReport';
	}

	/**
	 * Inclusive date range for the GA4 query.
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
