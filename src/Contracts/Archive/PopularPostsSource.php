<?php
/**
 * Popular-posts source contract (Blog/Top empty-state fallback).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies the IDs of the most popular published posts, when a popularity signal
 * is available (ticket 402, tier 2).
 *
 * The signal is external and optional — e.g. Jetpack Stats. Implementations must
 * be capability-checked: {@see self::is_available()} reports whether the backing
 * source is present, so {@see self::top_post_ids()} is only consulted when it
 * can return real data. A site without the source uses the
 * {@see \CannyForge\Archive\Core\Archive\NullPopularPostsSource} no-op.
 */
interface PopularPostsSource {
	/**
	 * Whether the backing popularity source is present and usable.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * The most popular published post IDs, most popular first, capped at $limit.
	 *
	 * Returns an empty array when no data is available (even if the source is
	 * nominally present), so callers can fall through to the next tier.
	 *
	 * @param int $limit Maximum number of IDs to return.
	 * @return int[]
	 */
	public function top_post_ids( int $limit ): array;
}
