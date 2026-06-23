<?php
/**
 * Ordered composite popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\PopularPostsSource;

/**
 * Combines several popularity sources under a strict first-available precedence.
 *
 * Given an ordered list of {@see PopularPostsSource}s, the composite is
 * available when any member is, and returns the IDs of the first member that is
 * available *and* yields data. This is the documented policy for relating
 * multiple Google signals (ticket 406): Search Console is listed first and GA4
 * second, so GA4 is purely additive and never weakens the Search Console path.
 *
 * Pure orchestration over the contract — it holds no Google dependency, so it
 * lives in Core and can occupy the Blog provider's top-tier source slot.
 */
final class CompositePopularPostsSource implements PopularPostsSource {
	/**
	 * Member sources in precedence order (first wins).
	 *
	 * @var PopularPostsSource[]
	 */
	private array $sources;

	/**
	 * Construct the composite.
	 *
	 * @param PopularPostsSource ...$sources Member sources, in precedence order.
	 */
	public function __construct( PopularPostsSource ...$sources ) {
		$this->sources = $sources;
	}

	/**
	 * Whether any member source is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		foreach ( $this->sources as $source ) {
			if ( $source->is_available() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * IDs from the first available member that returns data.
	 *
	 * A member that is available but returns no IDs is skipped so a stale-but-
	 * connected higher-precedence source cannot mask a lower one with real data.
	 *
	 * @param int $limit Maximum number of IDs to return.
	 * @return int[]
	 */
	public function top_post_ids( int $limit ): array {
		if ( $limit < 1 ) {
			return array();
		}

		foreach ( $this->sources as $source ) {
			if ( ! $source->is_available() ) {
				continue;
			}

			$ids = $source->top_post_ids( $limit );
			if ( array() !== $ids ) {
				return $ids;
			}
		}

		return array();
	}
}
