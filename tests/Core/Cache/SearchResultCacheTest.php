<?php
/**
 * Tests for the whole-database search response cache.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Cache;

use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Core\Cache\SearchResultCache;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the search response cache (ticket 608).
 */
class SearchResultCacheTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		TransientStore::reset();
		OptionStore::reset();
	}

	public function test_get_returns_false_on_miss(): void {
		$cache = new SearchResultCache();

		$this->assertFalse( $cache->get( new ContentQuery( 'anything' ) ) );
	}

	public function test_set_and_get_round_trip(): void {
		$cache   = new SearchResultCache();
		$query   = new ContentQuery( 'crawl budget' );
		$payload = array(
			'html'  => '<nav>results</nav>',
			'total' => 3,
		);

		$cache->set( $query, $payload );

		$this->assertSame( $payload, $cache->get( $query ) );
	}

	/**
	 * Two distinct queries never collide — each combination of search/filter/
	 * paging parameters is its own cache entry.
	 *
	 * @return void
	 */
	public function test_distinct_queries_cache_independently(): void {
		$cache = new SearchResultCache();

		$search_query   = new ContentQuery( 'tennis' );
		$category_query = new ContentQuery( '', 'news' );

		$cache->set( $search_query, array( 'html' => 'tennis-results' ) );
		$cache->set( $category_query, array( 'html' => 'news-results' ) );

		$this->assertSame( array( 'html' => 'tennis-results' ), $cache->get( $search_query ) );
		$this->assertSame( array( 'html' => 'news-results' ), $cache->get( $category_query ) );
	}

	/**
	 * Two requests that normalise to the same query (page/per_page included)
	 * share a single cache entry.
	 *
	 * @return void
	 */
	public function test_equivalent_queries_share_a_cache_entry(): void {
		$cache = new SearchResultCache();

		$cache->set( new ContentQuery( 'tennis', '', '', '', '', 2, 20 ), array( 'html' => 'page-2' ) );

		$this->assertSame( array( 'html' => 'page-2' ), $cache->get( new ContentQuery( 'tennis', '', '', '', '', 2, 20 ) ) );
	}

	/**
	 * A different page number is a cache miss against another page's entry.
	 *
	 * @return void
	 */
	public function test_different_page_is_a_cache_miss(): void {
		$cache = new SearchResultCache();

		$cache->set( new ContentQuery( 'tennis', '', '', '', '', 1, 20 ), array( 'html' => 'page-1' ) );

		$this->assertFalse( $cache->get( new ContentQuery( 'tennis', '', '', '', '', 2, 20 ) ) );
	}

	/**
	 * Clearing the cache invalidates every previously-cached response — even
	 * though clear() has no knowledge of which specific queries were cached
	 * (the generation-counter strategy; see the class docblock).
	 *
	 * @return void
	 */
	public function test_clear_invalidates_all_previously_cached_responses(): void {
		$cache = new SearchResultCache();

		$query_one = new ContentQuery( 'tennis' );
		$query_two = new ContentQuery( '', 'news' );

		$cache->set( $query_one, array( 'html' => 'tennis-results' ) );
		$cache->set( $query_two, array( 'html' => 'news-results' ) );

		$cache->clear();

		$this->assertFalse( $cache->get( $query_one ) );
		$this->assertFalse( $cache->get( $query_two ) );
	}

	/**
	 * After a clear, a fresh set/get round trip on the same query still works
	 * — the generation bump doesn't break the cache going forward, only the
	 * entries written under the previous generation.
	 *
	 * @return void
	 */
	public function test_cache_works_again_after_a_clear(): void {
		$cache = new SearchResultCache();
		$query = new ContentQuery( 'tennis' );

		$cache->set( $query, array( 'html' => 'stale' ) );
		$cache->clear();
		$cache->set( $query, array( 'html' => 'fresh' ) );

		$this->assertSame( array( 'html' => 'fresh' ), $cache->get( $query ) );
	}
}
