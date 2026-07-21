<?php
/**
 * Tests for the per-IP search endpoint throttle.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\RateLimit;

use CannyForge\Archive\Core\RateLimit\SearchThrottle;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the search endpoint's per-IP throttle (ticket 608).
 */
class SearchThrottleTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		TransientStore::reset();
		HookSpy::reset();
	}

	public function test_requests_under_the_limit_are_not_exceeded(): void {
		$this->lower_limit_to( 3 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.1' ) );
		$this->assertFalse( $throttle->is_exceeded( '203.0.113.1' ) );
		$this->assertFalse( $throttle->is_exceeded( '203.0.113.1' ) );
	}

	/**
	 * The (n+1)th request in a window trips the throttle.
	 *
	 * @return void
	 */
	public function test_request_beyond_the_limit_is_exceeded(): void {
		$this->lower_limit_to( 2 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.5' ) );
		$this->assertFalse( $throttle->is_exceeded( '203.0.113.5' ) );
		$this->assertTrue( $throttle->is_exceeded( '203.0.113.5' ) );
	}

	/**
	 * Once tripped, every further request in the same window keeps being
	 * rejected (not just the one that crossed the line).
	 *
	 * @return void
	 */
	public function test_stays_exceeded_for_further_requests_in_the_same_window(): void {
		$this->lower_limit_to( 1 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.9' ) );
		$this->assertTrue( $throttle->is_exceeded( '203.0.113.9' ) );
		$this->assertTrue( $throttle->is_exceeded( '203.0.113.9' ) );
	}

	/**
	 * Different IPs are tracked independently — one hostile client tripping
	 * the throttle never affects another visitor's own counter.
	 *
	 * @return void
	 */
	public function test_different_ips_are_tracked_independently(): void {
		$this->lower_limit_to( 1 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.10' ) );
		$this->assertTrue( $throttle->is_exceeded( '203.0.113.10' ) );

		// A different IP starts with its own, unaffected counter.
		$this->assertFalse( $throttle->is_exceeded( '203.0.113.11' ) );
	}

	/**
	 * Resetting an IP's counter clears a trip, simulating the next window
	 * without waiting on wall-clock time.
	 *
	 * @return void
	 */
	public function test_reset_clears_a_tripped_counter(): void {
		$this->lower_limit_to( 1 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.20' ) );
		$this->assertTrue( $throttle->is_exceeded( '203.0.113.20' ) );

		$throttle->reset( '203.0.113.20' );

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.20' ) );
	}

	/**
	 * An empty/unresolvable IP is never throttled — the endpoint fails open
	 * rather than bucketing every such request together.
	 *
	 * @return void
	 */
	public function test_empty_ip_is_never_exceeded(): void {
		$this->lower_limit_to( 1 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '' ) );
		$this->assertFalse( $throttle->is_exceeded( '' ) );
		$this->assertFalse( $throttle->is_exceeded( '' ) );
	}

	/**
	 * The per-window limit is filterable via `SearchThrottle::LIMIT_FILTER`,
	 * per the `cannyforge_archive_*` hook convention (docs/HOOKS.md).
	 *
	 * @return void
	 */
	public function test_limit_is_filterable(): void {
		$this->assertSame( SearchThrottle::LIMIT_FILTER, 'cannyforge_archive_search_throttle_limit' );

		$this->lower_limit_to( 1 );
		$throttle = new SearchThrottle();

		$this->assertFalse( $throttle->is_exceeded( '203.0.113.30' ) );
		$this->assertTrue( $throttle->is_exceeded( '203.0.113.30' ) );
	}

	/**
	 * The window length is filterable via `SearchThrottle::WINDOW_FILTER`.
	 *
	 * @return void
	 */
	public function test_window_is_filterable(): void {
		$this->assertSame( SearchThrottle::WINDOW_FILTER, 'cannyforge_archive_search_throttle_window' );
	}

	/**
	 * Lower the request limit for the duration of a test via the filter
	 * hook, so a trip can be produced deterministically without issuing the
	 * default's worth of requests.
	 *
	 * @param int $limit The limit to enforce.
	 * @return void
	 */
	private function lower_limit_to( int $limit ): void {
		add_filter(
			SearchThrottle::LIMIT_FILTER,
			static fn () => $limit
		);
	}
}
