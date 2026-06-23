<?php
/**
 * Tests for the cached GA4 post-ID store.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * GA4 post IDs are cached locally for page-render reads.
 */
class Ga4CacheStoreTest extends TestCase {
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
	 * Saving IDs de-duplicates and filters non-positive values.
	 *
	 * @return void
	 */
	public function test_save_cleans_and_get_returns_ids(): void {
		$store = new Ga4CacheStore();
		$store->save_post_ids( array( 10, 10, 0, -4, 7, 7 ) );

		$this->assertSame( array( 10, 7 ), $store->get_post_ids() );
	}

	/**
	 * Clearing the cache yields no IDs.
	 *
	 * @return void
	 */
	public function test_clear_empties_cache(): void {
		$store = new Ga4CacheStore();
		$store->save_post_ids( array( 10, 7 ) );
		$store->clear();

		$this->assertSame( array(), $store->get_post_ids() );
	}

	/**
	 * The GA4 cache uses a distinct option key from Search Console, so the two
	 * signals never overwrite each other.
	 *
	 * @return void
	 */
	public function test_ga4_cache_is_independent_of_search_console_cache(): void {
		$ga4 = new Ga4CacheStore();
		$ga4->save_post_ids( array( 1, 2 ) );

		$search = new SearchConsoleCacheStore();
		$search->save_post_ids( array( 3, 4 ) );

		$this->assertSame( array( 1, 2 ), $ga4->get_post_ids() );
		$this->assertSame( array( 3, 4 ), $search->get_post_ids() );
		$this->assertNotSame( Ga4CacheStore::OPTION_KEY, SearchConsoleCacheStore::OPTION_KEY );
	}
}
