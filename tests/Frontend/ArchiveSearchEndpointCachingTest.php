<?php
/**
 * Tests for the archive search endpoint's response cache (ticket 608).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ContentPage;
use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Core\Cache\SearchResultCache;
use CannyForge\Archive\Tests\AjaxResponseSpy;
use CannyForge\Archive\Tests\FakeContentIndexProvider;

/**
 * A hot search response is served from cache instead of reaching {@see
 * \CannyForge\Archive\Core\Archive\ContentIndexProvider} again (ticket 608:
 * the whole-database search endpoint previously ran an uncached `WP_Query`
 * on every request). Shared fixture/helpers live in {@see
 * ArchiveSearchEndpointTestCase}.
 */
class ArchiveSearchEndpointCachingTest extends ArchiveSearchEndpointTestCase {
	/**
	 * A second, identical request is served from cache: the provider is
	 * queried once, not twice, and the second response matches the first.
	 *
	 * @return void
	 */
	public function test_second_identical_request_is_served_from_cache(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array(
			'nonce'  => 'valid-nonce',
			'search' => 'crawl budget',
		);

		$entries = array( new ArchiveEntry( 'https://example.test/post-1/', 'Crawl Budget 101' ) );
		$page    = new ContentPage( $entries, 1, 1, 20 );
		$index   = new FakeContentIndexProvider( $page );
		$cache   = new SearchResultCache();

		$endpoint = $this->endpoint( $index, $cache );

		$endpoint->handle();
		$this->assertSame( 1, $index->call_count() );
		$first_response = AjaxResponseSpy::success();

		AjaxResponseSpy::reset();
		$endpoint->handle();

		$this->assertSame( 1, $index->call_count(), 'A cache hit must not query the provider again.' );
		$this->assertTrue( AjaxResponseSpy::has_success() );
		$this->assertSame( $first_response, AjaxResponseSpy::success() );
	}

	/**
	 * A different query is not served from another query's cache entry — it
	 * still reaches the provider.
	 *
	 * @return void
	 */
	public function test_a_different_query_is_not_served_from_the_others_cache(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$index    = new FakeContentIndexProvider( new ContentPage( array(), 0, 1, 20 ) );
		$cache    = new SearchResultCache();
		$endpoint = $this->endpoint( $index, $cache );

		$_REQUEST = array(
			'nonce'  => 'valid-nonce',
			'search' => 'tennis',
		);
		$endpoint->handle();

		$_REQUEST = array(
			'nonce'  => 'valid-nonce',
			'search' => 'football',
		);
		$endpoint->handle();

		$this->assertSame( 2, $index->call_count(), 'Two distinct queries must each reach the provider once.' );
	}

	/**
	 * A cache miss populates the cache with the payload just built — proven
	 * directly against the cache object rather than a second `handle()` call.
	 *
	 * @return void
	 */
	public function test_a_cache_miss_populates_the_cache(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array(
			'nonce'  => 'valid-nonce',
			'search' => 'crawl budget',
		);

		$entries = array( new ArchiveEntry( 'https://example.test/post-1/', 'Crawl Budget 101' ) );
		$index   = new FakeContentIndexProvider( new ContentPage( $entries, 1, 1, 20 ) );
		$cache   = new SearchResultCache();

		$this->endpoint( $index, $cache )->handle();

		$cached = $cache->get( new ContentQuery( 'crawl budget' ) );
		$this->assertIsArray( $cached );
		$this->assertStringContainsString( 'Crawl Budget 101', $cached['html'] );
	}
}
