<?php
/**
 * Tests for the public, unauthenticated archive search AJAX endpoint.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ContentPage;
use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Core\Archive\ContentIndexProvider;
use CannyForge\Archive\Frontend\ArchiveSearchEndpoint;
use CannyForge\Archive\Tests\AjaxResponseSpy;
use CannyForge\Archive\Tests\FakeContentIndexProvider;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\WpDieException;

/**
 * `ArchiveSearchEndpoint` is the plugin's only public `nopriv` AJAX endpoint
 * (ticket 601): nonce binding, request sanitisation, and public-content-only
 * exposure are all security-relevant promises made in its docblock but never
 * pinned by a test before this. Every test builds the endpoint against the
 * real, `OptionStore`-backed settings repository and the real `ArchiveRenderer`
 * (both cheap, deterministic collaborators), swapping in {@see
 * FakeContentIndexProvider} for the one collaborator that would otherwise
 * reach into `WP_Query` — so assertions land on the `ContentQuery` the
 * endpoint actually builds, not on `WP_Query` internals.
 *
 * Response caching and the per-IP throttle (both ticket 608) are covered
 * separately in {@see ArchiveSearchEndpointCachingTest} and {@see
 * ArchiveSearchEndpointThrottleTest}; shared fixture/helpers live in {@see
 * ArchiveSearchEndpointTestCase}.
 */
class ArchiveSearchEndpointTest extends ArchiveSearchEndpointTestCase {
	// -- Registration ------------------------------------------------------

	/**
	 * Registration wires both the logged-in and anonymous (`nopriv`) AJAX
	 * hooks — the endpoint's entire reason for existing is being reachable by
	 * logged-out visitors.
	 *
	 * @return void
	 */
	public function test_register_wires_both_logged_in_and_nopriv_hooks(): void {
		$this->endpoint( new FakeContentIndexProvider() )->register();

		$this->assertTrue( HookSpy::has( 'wp_ajax_' . ArchiveSearchEndpoint::ACTION ) );
		$this->assertTrue( HookSpy::has( 'wp_ajax_nopriv_' . ArchiveSearchEndpoint::ACTION ) );
	}

	// -- Nonce verification --------------------------------------------------

	/**
	 * With no nonce present at all, the request is rejected before any query
	 * is built or run.
	 *
	 * @return void
	 */
	public function test_missing_nonce_errors_and_runs_no_query(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = false;
		$_REQUEST                                      = array( 'search' => 'anything' );
		$index = new FakeContentIndexProvider();

		$this->expectException( WpDieException::class );

		try {
			$this->endpoint( $index )->handle();
		} finally {
			$this->assertSame( 0, $index->call_count() );
			$this->assertFalse( AjaxResponseSpy::has_success() );
		}
	}

	/**
	 * With an invalid nonce present, the request is likewise rejected before
	 * any query is built or run.
	 *
	 * @return void
	 */
	public function test_invalid_nonce_errors_and_runs_no_query(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = false;
		$_REQUEST                                      = array(
			'nonce'  => 'not-a-real-nonce',
			'search' => 'anything',
		);
		$index = new FakeContentIndexProvider();

		$this->expectException( WpDieException::class );

		try {
			$this->endpoint( $index )->handle();
		} finally {
			$this->assertSame( 0, $index->call_count() );
			$this->assertFalse( AjaxResponseSpy::has_success() );
		}
	}

	// -- Valid request: JSON payload shape -----------------------------------

	/**
	 * A valid request returns a success payload whose shape matches the JS
	 * contract (`assets/js/archive-filters.js`): rendered `html`, `total`,
	 * `page`, `per_page`, and the pagination metadata (`total_pages`,
	 * `has_next`, `has_prev`) the client uses to build its pager, plus
	 * `is_active` (whether the query was constrained at all).
	 *
	 * @return void
	 */
	public function test_valid_request_returns_the_expected_payload_shape(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array(
			'nonce'    => 'valid-nonce',
			'search'   => 'crawl budget',
			'page'     => '2',
			'per_page' => '20',
		);

		$entries = array( new ArchiveEntry( 'https://example.test/post-1/', 'Crawl Budget 101' ) );
		$page    = new ContentPage( $entries, 42, 2, 20 );
		$index   = new FakeContentIndexProvider( $page );

		$this->endpoint( $index )->handle();

		$this->assertTrue( AjaxResponseSpy::has_success() );
		$this->assertFalse( AjaxResponseSpy::has_error() );

		$data = AjaxResponseSpy::success();
		$this->assertIsArray( $data );
		foreach ( array( 'html', 'total', 'page', 'per_page', 'total_pages', 'has_next', 'has_prev', 'is_active' ) as $key ) {
			$this->assertArrayHasKey( $key, $data );
		}

		$this->assertStringContainsString( 'Crawl Budget 101', $data['html'] );
		$this->assertSame( 42, $data['total'] );
		$this->assertSame( 2, $data['page'] );
		$this->assertSame( 20, $data['per_page'] );
		$this->assertSame( 3, $data['total_pages'] ); // ceil( 42 / 20 ).
		$this->assertTrue( $data['has_next'] );
		$this->assertTrue( $data['has_prev'] );
		$this->assertTrue( $data['is_active'] );
	}

	/**
	 * With no search term and no filters, the query is reported as inactive —
	 * the front-end uses this to decide whether to show the promoted default
	 * view instead of whole-database results.
	 *
	 * @return void
	 */
	public function test_empty_request_reports_an_inactive_query(): void {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array( 'nonce' => 'valid-nonce' );
		$index = new FakeContentIndexProvider( new ContentPage( array(), 0, 1, 20 ) );

		$this->endpoint( $index )->handle();

		$this->assertFalse( AjaxResponseSpy::success()['is_active'] );
	}

	// -- Hostile input handling -----------------------------------------------

	/**
	 * An absurd `per_page` is clamped to `ContentQuery::MAX_PER_PAGE` before it
	 * ever reaches the index provider — never passed through raw.
	 *
	 * @return void
	 */
	public function test_oversized_per_page_is_clamped_before_reaching_the_provider(): void {
		$query = $this->run_and_capture_query( array( 'per_page' => '999999' ) );

		$this->assertSame( ContentQuery::MAX_PER_PAGE, $query->per_page() );
	}

	/**
	 * A negative page number can never reach the provider as negative — it is
	 * coerced to a positive page before the query is built.
	 *
	 * @return void
	 */
	public function test_negative_page_never_reaches_the_provider_as_negative(): void {
		$query = $this->run_and_capture_query( array( 'page' => '-1' ) );

		$this->assertSame( 1, $query->page() );
	}

	/**
	 * A wildly oversized search string is clamped to a bounded length before
	 * it ever reaches the index provider.
	 *
	 * @return void
	 */
	public function test_oversized_search_string_is_clamped_before_reaching_the_provider(): void {
		$oversized = str_repeat( 'a', 5000 );

		$query = $this->run_and_capture_query( array( 'search' => $oversized ) );

		$this->assertSame( ContentQuery::MAX_SEARCH_LENGTH, strlen( $query->search() ) );
	}

	/**
	 * Unknown/unexpected request keys (anything not one of the five filter
	 * dimensions or pagination fields) are silently ignored — they can never
	 * influence the query, whatever they claim to be.
	 *
	 * @return void
	 */
	public function test_unknown_filter_keys_are_ignored(): void {
		$query = $this->run_and_capture_query(
			array(
				'category'       => 'news',
				'unknown_filter' => 'should-be-ignored',
				'post_status'    => 'draft',
				'orderby'        => 'title',
			)
		);

		$this->assertSame( 'news', $query->category() );
		$this->assertSame( '', $query->tag() );
		$this->assertSame( '', $query->author() );
		$this->assertSame( '', $query->month() );
	}

	/**
	 * Only published, public posts are ever requested — `ContentQuery` carries
	 * no status field at all, so a hostile request cannot smuggle a `draft` /
	 * `private` / `trash` status through. Proven end to end: the query the
	 * endpoint hands the provider, fed through the real
	 * `ContentIndexProvider::build_query_args()`, always yields
	 * `post_status => publish`.
	 *
	 * @return void
	 */
	public function test_only_published_status_is_ever_requested(): void {
		$query = $this->run_and_capture_query(
			array(
				'post_status' => 'draft,private,trash',
				'status'      => 'any',
			)
		);

		$args = ( new ContentIndexProvider() )->build_query_args( $query );

		$this->assertSame( 'publish', $args['post_status'] );
	}
}
