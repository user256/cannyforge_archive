<?php
/**
 * Tests for the cached Search Console post-ID store.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Search Console post IDs are cached locally for page-render reads.
 */
class SearchConsoleCacheStoreTest extends TestCase {
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
		$store = new SearchConsoleCacheStore();
		$store->save_post_ids( array( 10, 10, 0, -4, 7, 7 ) );

		$this->assertSame( array( 10, 7 ), $store->get_post_ids() );
	}

	/**
	 * Clearing the cache yields no IDs.
	 *
	 * @return void
	 */
	public function test_clear_empties_cache(): void {
		$store = new SearchConsoleCacheStore();
		$store->save_post_ids( array( 10, 7 ) );
		$store->clear();

		$this->assertSame( array(), $store->get_post_ids() );
	}

	/**
	 * Raw URLs are retained separately from matched local post IDs.
	 *
	 * @return void
	 */
	public function test_save_results_retains_clean_source_urls(): void {
		$store = new SearchConsoleCacheStore();
		$store->save_results( array(), array( 'https://example.test/a/', 'https://example.test/a/', '', 12 ) );

		$this->assertSame( array(), $store->get_post_ids() );
		$this->assertSame( array( 'https://example.test/a/' ), $store->get_source_urls() );
	}
}
