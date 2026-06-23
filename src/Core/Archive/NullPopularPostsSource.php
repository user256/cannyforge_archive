<?php
/**
 * No-op popular-posts source.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\PopularPostsSource;

/**
 * The default popular-posts source: never available, never returns IDs.
 *
 * Used when no popularity backend (e.g. Jetpack Stats) is wired, so
 * {@see BlogEntryProvider} can depend on the {@see PopularPostsSource} contract
 * unconditionally and simply fall through to the next fallback tier.
 */
final class NullPopularPostsSource implements PopularPostsSource {
	/**
	 * Never available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return false;
	}

	/**
	 * Never returns IDs.
	 *
	 * @param int $limit Ignored.
	 * @return int[]
	 */
	public function top_post_ids( int $limit ): array {
		return array();
	}
}
