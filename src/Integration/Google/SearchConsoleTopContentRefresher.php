<?php
/**
 * Refreshes cached Search Console-derived top post IDs.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates the Search Console fetch and URL→post mapping.
 */
final class SearchConsoleTopContentRefresher {
	/**
	 * Search Console API client.
	 *
	 * @var SearchConsoleClient
	 */
	private SearchConsoleClient $client;

	/**
	 * Cache store for the mapped post IDs.
	 *
	 * @var SearchConsoleCacheStore
	 */
	private SearchConsoleCacheStore $cache;

	/**
	 * Google settings store.
	 *
	 * @var GoogleSettingsStore
	 */
	private GoogleSettingsStore $settings;

	/**
	 * URL resolver: fn(string $url): int.
	 *
	 * @var callable
	 */
	private $url_to_postid;

	/**
	 * Post-status reader: fn(int $post_id): string.
	 *
	 * @var callable
	 */
	private $post_status;

	/**
	 * Construct the refresher.
	 *
	 * @param SearchConsoleClient     $client        Search Console client.
	 * @param SearchConsoleCacheStore $cache         Cache store.
	 * @param GoogleSettingsStore     $settings      Google settings store.
	 * @param callable|null           $url_to_postid URL resolver.
	 * @param callable|null           $post_status   Post-status reader.
	 */
	public function __construct(
		SearchConsoleClient $client,
		SearchConsoleCacheStore $cache,
		GoogleSettingsStore $settings,
		?callable $url_to_postid = null,
		?callable $post_status = null
	) {
		$this->client        = $client;
		$this->cache         = $cache;
		$this->settings      = $settings;
		$this->url_to_postid = $url_to_postid ?? static function ( string $url ): int {
			return function_exists( 'url_to_postid' ) ? (int) url_to_postid( $url ) : 0;
		};
		$this->post_status   = $post_status ?? static function ( int $post_id ): string {
			return function_exists( 'get_post_status' ) ? (string) get_post_status( $post_id ) : '';
		};
	}

	/**
	 * Refresh the cached top post IDs from Search Console.
	 *
	 * @param int $limit Maximum number of post IDs to cache.
	 * @return int[]
	 */
	public function refresh( int $limit ): array {
		$settings    = $this->settings->get();
		$rows        = $this->client->query_top_pages(
			$settings->search_console_site_url(),
			$settings->report_window_days(),
			$limit
		);
		$source_urls = $this->client->extract_page_urls( $rows, max( 1, $limit * 3 ) );
		$ids         = $this->map_rows_to_post_ids(
			$rows,
			$this->client,
			$this->url_to_postid,
			$this->post_status,
			$limit
		);

		$this->cache->save_results( $ids, $source_urls );

		return $ids;
	}

	/**
	 * Map Search Console rows to clean local published post IDs.
	 *
	 * Extracts page URLs from the row set, resolves them to local post IDs,
	 * filters to positive IDs whose status is `publish`, de-duplicates, and caps.
	 *
	 * @param array<int, array<string, mixed>> $rows          Raw Search Console rows.
	 * @param SearchConsoleClient              $client        Search Console client.
	 * @param callable                         $url_to_postid URL resolver.
	 * @param callable                         $post_status   Post-status reader.
	 * @param int                              $limit         Maximum IDs to return.
	 * @return int[]
	 */
	public function map_rows_to_post_ids(
		array $rows,
		SearchConsoleClient $client,
		callable $url_to_postid,
		callable $post_status,
		int $limit
	): array {
		$ids = array();

		foreach ( $client->extract_page_urls( $rows, max( 1, $limit * 3 ) ) as $page ) {
			$post_id = (int) $url_to_postid( $page );

			if ( $post_id < 1 || 'publish' !== (string) $post_status( $post_id ) || in_array( $post_id, $ids, true ) ) {
				continue;
			}

			$ids[] = $post_id;
			if ( count( $ids ) >= $limit ) {
				break;
			}
		}

		return $ids;
	}
}
