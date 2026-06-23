<?php
/**
 * Tests for the Jetpack Stats popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Core\Archive\JetpackStatsSource;
use PHPUnit\Framework\TestCase;

/**
 * Availability gating and row→ID mapping, with Jetpack stubbed via injection.
 */
class JetpackStatsSourceTest extends TestCase {
	/**
	 * When the source reports unavailable, no IDs are returned and the fetcher is
	 * never called.
	 *
	 * @return void
	 */
	public function test_returns_nothing_when_unavailable(): void {
		$called = false;
		$source = new JetpackStatsSource(
			function ( int $limit ) use ( &$called ): array {
				$called = true;
				return array();
			},
			static fn (): bool => false
		);

		$this->assertFalse( $source->is_available() );
		$this->assertSame( array(), $source->top_post_ids( 10 ) );
		$this->assertFalse( $called, 'Fetcher must not run when unavailable.' );
	}

	/**
	 * Rows are mapped to post IDs in order when the source is available.
	 *
	 * @return void
	 */
	public function test_maps_rows_to_ids_when_available(): void {
		$source = new JetpackStatsSource(
			static fn ( int $limit ): array => array(
				array(
					'post_id' => '42',
					'views'   => 900,
				),
				array(
					'post_id' => 7,
					'views'   => 500,
				),
			),
			static fn (): bool => true
		);

		$this->assertTrue( $source->is_available() );
		$this->assertSame( array( 42, 7 ), $source->top_post_ids( 10 ) );
	}

	/**
	 * The mapper skips rows without a positive integer post_id, de-duplicates, and
	 * respects the limit.
	 *
	 * @return void
	 */
	public function test_map_rows_filters_dedupes_and_caps(): void {
		$source = new JetpackStatsSource( static fn (): array => array(), static fn (): bool => true );

		$rows = array(
			array( 'post_id' => 5 ),
			array( 'views' => 10 ),          // No post_id.
			array( 'post_id' => 0 ),         // Non-positive.
			array( 'post_id' => 5 ),         // Duplicate.
			array( 'post_id' => 8 ),
			array( 'post_id' => 13 ),
		);

		$this->assertSame( array( 5, 8 ), $source->map_rows( $rows, 2 ) );
	}

	/**
	 * A zero/negative limit yields no IDs.
	 *
	 * @return void
	 */
	public function test_zero_limit_returns_nothing(): void {
		$source = new JetpackStatsSource(
			static fn ( int $limit ): array => array( array( 'post_id' => 1 ) ),
			static fn (): bool => true
		);

		$this->assertSame( array(), $source->top_post_ids( 0 ) );
	}
}
