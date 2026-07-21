<?php
/**
 * Tests for the archive search endpoint's per-IP throttle (ticket 608).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Core\RateLimit\SearchThrottle;
use CannyForge\Archive\Tests\AjaxResponseSpy;
use CannyForge\Archive\Tests\FakeContentIndexProvider;
use CannyForge\Archive\Tests\HookSpy;

/**
 * The endpoint is the plugin's only public `nopriv` action; a nonce alone
 * does not bound scripted request volume (ticket 608). Shared fixture/
 * helpers live in {@see ArchiveSearchEndpointTestCase}.
 */
class ArchiveSearchEndpointThrottleTest extends ArchiveSearchEndpointTestCase {
	/**
	 * A request beyond the throttle's per-window limit is rejected with a
	 * 429-style JSON error and never reaches the provider.
	 *
	 * @return void
	 */
	public function test_request_beyond_the_throttle_limit_returns_429_and_runs_no_query(): void {
		add_filter( SearchThrottle::LIMIT_FILTER, static fn () => 1 );

		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array( 'nonce' => 'valid-nonce' );
		$_SERVER['REMOTE_ADDR']                        = '203.0.113.77';

		$index    = new FakeContentIndexProvider();
		$endpoint = $this->endpoint( $index, null, new SearchThrottle() );

		// First request consumes the only allowed slot in this window.
		$endpoint->handle();
		$this->assertSame( 1, $index->call_count() );
		AjaxResponseSpy::reset();

		// Second request in the same window trips the throttle.
		$endpoint->handle();

		$this->assertSame( 1, $index->call_count(), 'A throttled request must never reach the provider.' );
		$this->assertFalse( AjaxResponseSpy::has_success() );
		$this->assertTrue( AjaxResponseSpy::has_error() );
		$this->assertTrue( HookSpy::has( 'status_header:429' ), 'A throttled request must respond with HTTP 429.' );
	}

	/**
	 * Different IPs are throttled independently — one IP tripping the limit
	 * never blocks a different IP's own request.
	 *
	 * @return void
	 */
	public function test_throttle_is_per_ip(): void {
		add_filter( SearchThrottle::LIMIT_FILTER, static fn () => 1 );

		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;

		$index    = new FakeContentIndexProvider();
		$throttle = new SearchThrottle();
		$endpoint = $this->endpoint( $index, null, $throttle );

		// Distinct search terms so the two requests can't be served from each
		// other's response cache — this test isolates throttle behaviour only.
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$_REQUEST               = array(
			'nonce'  => 'valid-nonce',
			'search' => 'first ip',
		);
		$endpoint->handle();

		$_SERVER['REMOTE_ADDR'] = '203.0.113.11';
		$_REQUEST               = array(
			'nonce'  => 'valid-nonce',
			'search' => 'second ip',
		);
		$endpoint->handle();

		$this->assertSame( 2, $index->call_count(), 'A different IP must not be blocked by another IP tripping the limit.' );
		$this->assertTrue( AjaxResponseSpy::has_success() );
	}

	/**
	 * After the throttle is reset for an IP (simulating the next window), the
	 * endpoint allows a request from it again.
	 *
	 * @return void
	 */
	public function test_endpoint_allows_a_request_again_after_the_throttle_is_reset(): void {
		add_filter( SearchThrottle::LIMIT_FILTER, static fn () => 1 );

		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array( 'nonce' => 'valid-nonce' );
		$_SERVER['REMOTE_ADDR']                        = '203.0.113.88';

		$throttle = new SearchThrottle();
		// Simulate a slot already consumed by earlier traffic in this window.
		$throttle->is_exceeded( '203.0.113.88' );
		$throttle->reset( '203.0.113.88' );

		$index = new FakeContentIndexProvider();
		$this->endpoint( $index, null, $throttle )->handle();

		$this->assertSame( 1, $index->call_count() );
		$this->assertTrue( AjaxResponseSpy::has_success() );
	}
}
