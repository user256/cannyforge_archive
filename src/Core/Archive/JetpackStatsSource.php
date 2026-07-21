<?php
/**
 * Jetpack Stats popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\PopularPostsSource;

/**
 * Reads top post-view data from Jetpack Stats, when that plugin is active.
 *
 * Jetpack exposes `stats_get_csv()` (the classic Stats module) which returns
 * top-post rows including a `post_id`. This adapter is entirely capability-gated:
 * when the function is absent — Jetpack not installed, or Stats not enabled — it
 * reports unavailable and returns no IDs, so the caller falls through to the next
 * fallback tier (ticket 402, tier 2). No hard dependency on Jetpack is taken.
 *
 * The Jetpack call is isolated behind an injected fetcher so the row→ID mapping
 * stays unit-testable without Jetpack present.
 */
final class JetpackStatsSource implements PopularPostsSource {
	/**
	 * Fetches raw Jetpack Stats top-post rows: fn(int $limit): array<int, array>.
	 *
	 * @var callable
	 */
	private $fetcher;

	/**
	 * Reports whether the Jetpack Stats backend is present: fn(): bool.
	 *
	 * @var callable
	 */
	private $availability;

	/**
	 * Construct the source.
	 *
	 * Defaults bind to Jetpack's global `stats_get_csv()`; both collaborators are
	 * injectable so the mapping logic can be tested without Jetpack.
	 *
	 * @param callable|null $fetcher      fn(int $limit): array<int, array<string, mixed>>.
	 * @param callable|null $availability fn(): bool.
	 */
	public function __construct( ?callable $fetcher = null, ?callable $availability = null ) {
		$this->availability = $availability ?? static function (): bool {
			return function_exists( 'stats_get_csv' );
		};
		$this->fetcher      = $fetcher ?? static function ( int $limit ): array {
			// `stats_get_csv` is a Jetpack global that only exists when Jetpack
			// Stats is active, which is_available() guarantees before this runs.
			// PHPStan can't see the optional dependency, so the undefined-function
			// error is ignored here by design.
			// @phpstan-ignore-next-line function.notFound
			$rows = stats_get_csv(
				'postviews',
				array(
					'days'  => 30,
					'limit' => $limit,
				)
			);

			return is_array( $rows ) ? $rows : array();
		};
	}

	/**
	 * Whether Jetpack Stats is present and usable.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return (bool) ( $this->availability )();
	}

	/**
	 * The top post IDs by Jetpack Stats post-views, most-viewed first.
	 *
	 * @param int $limit Maximum number of IDs to return.
	 * @return int[]
	 */
	public function top_post_ids( int $limit ): array {
		if ( $limit < 1 || ! $this->is_available() ) {
			return array();
		}

		$rows = ( $this->fetcher )( $limit );

		return $this->map_rows( is_array( $rows ) ? $rows : array(), $limit );
	}

	/**
	 * Map raw Jetpack Stats rows to a clean, de-duplicated list of post IDs.
	 *
	 * Pure: takes the row set Jetpack would return and yields the post IDs. Rows
	 * without a positive integer `post_id` are skipped; order (most-viewed first)
	 * is preserved.
	 *
	 * @param array<int, array<string, mixed>> $rows  Raw Jetpack Stats rows.
	 * @param int                              $limit Maximum number of IDs.
	 * @return int[]
	 */
	public function map_rows( array $rows, int $limit ): array {
		$ids = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['post_id'] ) || ! is_numeric( $row['post_id'] ) ) {
				continue;
			}

			$id = (int) $row['post_id'];
			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}

			if ( count( $ids ) >= $limit ) {
				break;
			}
		}

		return $ids;
	}
}
