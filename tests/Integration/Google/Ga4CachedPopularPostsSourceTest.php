<?php
/**
 * Tests for the cached GA4 popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4CachedPopularPostsSource;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * The page-render GA4 source reads only cached IDs.
 */
class Ga4CachedPopularPostsSourceTest extends TestCase {
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
	 * The source is available only when a GA4 property is set and connected.
	 *
	 * @return void
	 */
	public function test_is_available_requires_property_id_and_connected_status(): void {
		$settings = new GoogleSettingsStore();
		$tokens   = new GoogleTokenStore();
		$cache    = new Ga4CacheStore();
		$source   = new Ga4CachedPopularPostsSource( $cache, $settings, $tokens );

		$this->assertFalse( $source->is_available() );

		// Connected but no GA4 property → still unavailable (Search Console only).
		$settings->save( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com', 30 ) );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );
		$this->assertFalse( $source->is_available() );

		// Add a GA4 property → available.
		$settings->save( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com', 30, '123456789' ) );
		$this->assertTrue( $source->is_available() );
	}

	/**
	 * The source returns cached IDs, capped to the requested limit.
	 *
	 * @return void
	 */
	public function test_top_post_ids_reads_cache_when_available(): void {
		$settings = new GoogleSettingsStore();
		$settings->save( new GoogleSettings( 'client-id', 'client-secret', '', 30, '123456789' ) );

		$tokens = new GoogleTokenStore();
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$cache = new Ga4CacheStore();
		$cache->save_post_ids( array( 10, 7, 3 ) );

		$source = new Ga4CachedPopularPostsSource( $cache, $settings, $tokens );

		$this->assertSame( array( 10, 7 ), $source->top_post_ids( 2 ) );
	}
}
