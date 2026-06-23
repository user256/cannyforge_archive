<?php
/**
 * Cached GA4 popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

use CannyForge\Archive\Contracts\Archive\PopularPostsSource;

/**
 * Exposes cached GA4 IDs to the Blog fallback chain.
 *
 * Like {@see SearchConsoleCachedPopularPostsSource}, this source never calls
 * Google during page render; it reads only from the local cache written by the
 * GA4 refresh flow. It is available only when a GA4 property is configured and
 * the Google account is connected.
 */
final class Ga4CachedPopularPostsSource implements PopularPostsSource {
	/**
	 * Cache store.
	 *
	 * @var Ga4CacheStore
	 */
	private Ga4CacheStore $cache;

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
	 * @param Ga4CacheStore       $cache    Cache store.
	 * @param GoogleSettingsStore $settings Google settings store.
	 * @param GoogleTokenStore    $tokens   Google token store.
	 */
	public function __construct(
		Ga4CacheStore $cache,
		GoogleSettingsStore $settings,
		GoogleTokenStore $tokens
	) {
		$this->cache    = $cache;
		$this->settings = $settings;
		$this->tokens   = $tokens;
	}

	/**
	 * Whether GA4 is configured and the account is connected.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return '' !== $this->settings->get()->ga4_property_id()
			&& GoogleTokenStore::STATUS_CONNECTED === $this->tokens->status();
	}

	/**
	 * Cached GA4-derived post IDs, most viewed first.
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
