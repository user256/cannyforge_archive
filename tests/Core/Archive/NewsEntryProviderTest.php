<?php
/**
 * Tests for the News-mode entry provider's window selection.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\NewsEntryProvider;
use PHPUnit\Framework\TestCase;

/**
 * The query args select the right window, status, ordering, and bound.
 */
class NewsEntryProviderTest extends TestCase {
	/**
	 * A fixed "now": 2026-06-19 12:00:00 UTC.
	 */
	private const NOW = 1781870400;

	/**
	 * Build args for a given window at the fixed "now".
	 *
	 * @param int $hours Window in hours.
	 * @return array<string, mixed>
	 */
	private function args( int $hours ): array {
		$settings = Settings::from_array(
			array(
				'mode'              => 'news',
				'news_window_hours' => $hours,
			)
		);

		return ( new NewsEntryProvider() )->build_query_args( $settings, self::NOW );
	}

	/**
	 * The cutoff is exactly `now - window_hours`, inclusive, on the GMT column.
	 *
	 * @return void
	 */
	public function test_cutoff_is_now_minus_window(): void {
		$args  = $this->args( 72 );
		$after = $args['date_query'][0];

		// 72h before NOW = 2026-06-16 12:00:00 UTC.
		$this->assertSame( '2026-06-16 12:00:00', $after['after'] );
		$this->assertTrue( $after['inclusive'] );
		$this->assertSame( 'post_date_gmt', $after['column'] );
	}

	/**
	 * The default window (72h) and a custom window produce different cutoffs.
	 *
	 * @return void
	 */
	public function test_window_hours_drives_the_cutoff(): void {
		$this->assertSame( '2026-06-19 11:00:00', $this->args( 1 )['date_query'][0]['after'] );
		$this->assertSame( '2026-06-18 12:00:00', $this->args( 24 )['date_query'][0]['after'] );
	}

	/**
	 * Only published posts, newest first, are selected.
	 *
	 * @return void
	 */
	public function test_selects_published_newest_first(): void {
		$args = $this->args( 72 );

		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 'date', $args['orderby'] );
		$this->assertSame( 'DESC', $args['order'] );
	}

	/**
	 * The query is bounded by the hard entry cap.
	 *
	 * @return void
	 */
	public function test_query_is_bounded(): void {
		$this->assertSame( NewsEntryProvider::MAX_ENTRIES, $this->args( 72 )['posts_per_page'] );
	}
}
