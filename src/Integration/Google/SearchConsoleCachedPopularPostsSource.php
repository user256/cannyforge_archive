<?php
/**
 * Cached Search Console popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

use CannyForge\Archive\Contracts\Archive\PopularPostsSource;

/**
 * Exposes cached Search Console IDs to the Blog fallback chain.
 *
 * This source never calls Google during page render; it reads only from the
 * local cache written by the refresh flow.
 */
final class SearchConsoleCachedPopularPostsSource implements PopularPostsSource {
	/**
	 * Cache store.
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
	 * Google token store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $tokens;

	/**
	 * Construct the source.
	 *
	 * @param SearchConsoleCacheStore $cache    Cache store.
	 * @param GoogleSettingsStore     $settings Google settings store.
	 * @param GoogleTokenStore        $tokens   Google token store.
	 */
	public function __construct(
		SearchConsoleCacheStore $cache,
		GoogleSettingsStore $settings,
		GoogleTokenStore $tokens
	) {
		$this->cache    = $cache;
		$this->settings = $settings;
		$this->tokens   = $tokens;
	}

	/**
	 * Whether Search Console is configured and connected.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return '' !== $this->settings->get()->search_console_site_url()
			&& GoogleTokenStore::STATUS_CONNECTED === $this->tokens->status();
	}

	/**
	 * Cached Search Console-derived post IDs, most clicked first.
	 *
	 * @param int $limit Maximum number of IDs to return.
	 * @return int[]
	 */
	public function top_post_ids( int $limit ): array {
		if ( $limit < 1 || ! $this->is_available() ) {
			return array();
		}

		return array_slice( $this->cache->get_post_ids(), 0, $limit );
	}
}
