<?php
/**
 * Tests for the cached Search Console popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCachedPopularPostsSource;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * The page-render source reads only cached Search Console IDs.
 */
class SearchConsoleCachedPopularPostsSourceTest extends TestCase {
	/**
	 * Reset the in-memory option store before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
	}

	/**
	 * The source is available only when configured and connected.
	 *
	 * @return void
	 */
	public function test_is_available_requires_site_url_and_connected_status(): void {
		$settings = new GoogleSettingsStore();
		$tokens   = new GoogleTokenStore();
		$cache    = new SearchConsoleCacheStore();
		$source   = new SearchConsoleCachedPopularPostsSource( $cache, $settings, $tokens );

		$this->assertFalse( $source->is_available() );

		$settings->save( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com', 30 ) );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$this->assertTrue( $source->is_available() );
	}

	/**
	 * The source returns cached IDs, capped to the requested limit.
	 *
	 * @return void
	 */
	public function test_top_post_ids_reads_cache_when_available(): void {
		$settings = new GoogleSettingsStore();
		$settings->save( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com', 30 ) );

		$tokens = new GoogleTokenStore();
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$cache = new SearchConsoleCacheStore();
		$cache->save_post_ids( array( 10, 7, 3 ) );

		$source = new SearchConsoleCachedPopularPostsSource( $cache, $settings, $tokens );

		$this->assertSame( array( 10, 7 ), $source->top_post_ids( 2 ) );
	}
}
