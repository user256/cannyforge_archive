<?php
/**
 * Tests for the ordered composite popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\PopularPostsSource;
use CannyForge\Archive\Core\Archive\CompositePopularPostsSource;
use PHPUnit\Framework\TestCase;

/**
 * The composite applies a strict first-available precedence across members.
 */
class CompositePopularPostsSourceTest extends TestCase {
	/**
	 * Unavailable when no member is available.
	 *
	 * @return void
	 */
	public function test_unavailable_when_no_member_available(): void {
		$composite = new CompositePopularPostsSource(
			$this->source( false, array( 1 ) ),
			$this->source( false, array( 2 ) )
		);

		$this->assertFalse( $composite->is_available() );
		$this->assertSame( array(), $composite->top_post_ids( 5 ) );
	}

	/**
	 * The first available member that returns data wins.
	 *
	 * @return void
	 */
	public function test_first_available_member_with_data_wins(): void {
		$composite = new CompositePopularPostsSource(
			$this->source( true, array( 10, 7 ) ),
			$this->source( true, array( 99 ) )
		);

		$this->assertTrue( $composite->is_available() );
		$this->assertSame( array( 10, 7 ), $composite->top_post_ids( 5 ) );
	}

	/**
	 * An available-but-empty higher-precedence source falls through to the next
	 * member with real data, so a stale primary cache cannot mask a secondary one.
	 *
	 * @return void
	 */
	public function test_falls_through_available_but_empty_member(): void {
		$composite = new CompositePopularPostsSource(
			$this->source( true, array() ),
			$this->source( true, array( 42 ) )
		);

		$this->assertSame( array( 42 ), $composite->top_post_ids( 5 ) );
	}

	/**
	 * An unavailable primary is skipped in favour of an available secondary.
	 *
	 * @return void
	 */
	public function test_skips_unavailable_primary(): void {
		$composite = new CompositePopularPostsSource(
			$this->source( false, array( 1 ) ),
			$this->source( true, array( 5, 6 ) )
		);

		$this->assertSame( array( 5, 6 ), $composite->top_post_ids( 5 ) );
	}

	/**
	 * A non-positive limit short-circuits to an empty result.
	 *
	 * @return void
	 */
	public function test_zero_limit_returns_empty(): void {
		$composite = new CompositePopularPostsSource( $this->source( true, array( 1, 2 ) ) );

		$this->assertSame( array(), $composite->top_post_ids( 0 ) );
	}

	/**
	 * Build a stub popular-posts source.
	 *
	 * @param bool  $available Whether the source reports as available.
	 * @param int[] $ids       IDs to return from top_post_ids().
	 * @return PopularPostsSource
	 */
	private function source( bool $available, array $ids ): PopularPostsSource {
		return new class( $available, $ids ) implements PopularPostsSource {
			/**
			 * Construct the stub.
			 *
			 * @param bool  $available Availability flag.
			 * @param int[] $ids       IDs to return.
			 */
			public function __construct( private bool $available, private array $ids ) {}

			/**
			 * Reported availability.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return $this->available;
			}

			/**
			 * Stubbed IDs, capped at the limit.
			 *
			 * @param int $limit Maximum IDs.
			 * @return int[]
			 */
			public function top_post_ids( int $limit ): array {
				return array_slice( $this->ids, 0, $limit );
			}
		};
	}
}
