<?php
/**
 * Server-rendered continuation for the optional complete archive.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ContentPage;
use CannyForge\Archive\Contracts\Settings\ContentSelection;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Builds later archive pages from all eligible local posts.
 *
 * The promoted first page is deliberately supplied by the caller after its
 * normal filters have run. This means only local post IDs actually rendered on
 * page one are excluded; curated external URLs never suppress a local post.
 *
 * Not `final`: unit tests stub {@see self::has_continuation()} when exercising
 * page-one HTML caching without a WordPress query runtime (ticket 731).
 */
class FullArchiveContinuationProvider extends ContentIndexProvider {
	/** Entries per server-rendered continuation page. */
	public const PER_PAGE = FullArchiveQueryArgsBuilder::PER_PAGE;

	/**
	 * Bounded continuation-query builder.
	 *
	 * @var FullArchiveQueryArgsBuilder
	 */
	private FullArchiveQueryArgsBuilder $queries;

	/**
	 * Construct the continuation provider.
	 *
	 * @param FullArchiveQueryArgsBuilder|null $queries Bounded query-argument builder.
	 */
	public function __construct( ?FullArchiveQueryArgsBuilder $queries = null ) {
		$this->queries = $queries ?? new FullArchiveQueryArgsBuilder();
	}

	/**
	 * Return one later archive page. Page one here means `/archive/page/2/`.
	 *
	 * @param Settings $settings     Current settings.
	 * @param int[]    $excluded_ids Stable local IDs actually rendered on `/archive/`.
	 * @param int      $page         Continuation page number, one-based.
	 * @return ContentPage
	 */
	public function provide_continuation( Settings $settings, array $excluded_ids, int $page ): ContentPage {
		$excluded_ids = $this->normalise_post_ids( $excluded_ids );
		$selection    = $settings->content_selection();
		$page         = max( 1, $page );
		$total        = $this->count_eligible( $selection, $excluded_ids );

		if ( 0 === $total || ( $page - 1 ) * self::PER_PAGE >= $total ) {
			return new ContentPage( array(), $total, $page, self::PER_PAGE );
		}

		$query   = new \WP_Query( $this->build_continuation_query_args( $selection, $excluded_ids, $page ) );
		$entries = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$entries[] = $this->map_post( $post );
			}
		}

		return new ContentPage( $entries, $total, $page, self::PER_PAGE );
	}

	/**
	 * Whether any eligible post remains after excluding page-one membership.
	 *
	 * Page one only needs a yes/no answer before rendering its continuation
	 * link, so this deliberately fetches at most one ID and skips found rows.
	 *
	 * @param Settings $settings     Current settings.
	 * @param int[]    $excluded_ids Stable local IDs actually rendered on page one.
	 * @return bool
	 */
	public function has_continuation( Settings $settings, array $excluded_ids ): bool {
		$query = new \WP_Query(
			$this->queries->existence(
				$settings->content_selection(),
				$this->normalise_post_ids( $excluded_ids )
			)
		);

		return array() !== $query->posts;
	}

	/**
	 * Build the bounded query used for one requested continuation page.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Page-one local post IDs.
	 * @param int              $page         Continuation page number.
	 * @return array<string, mixed>
	 */
	public function build_continuation_query_args( ContentSelection $selection, array $excluded_ids, int $page ): array {
		return $this->queries->page( $selection, $excluded_ids, $page );
	}

	/**
	 * Build the one-row query used to obtain the exact continuation total.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Page-one local post IDs.
	 * @return array<string, mixed>
	 */
	public function build_continuation_count_args( ContentSelection $selection, array $excluded_ids ): array {
		return $this->queries->count( $selection, $excluded_ids );
	}

	/**
	 * Extract stable local IDs from entries actually rendered on page one.
	 *
	 * @param ArchiveEntry[] $entries Page-one entries.
	 * @return int[]
	 */
	public function page_one_post_ids( array $entries ): array {
		$ids = array();
		foreach ( $entries as $entry ) {
			if ( $entry instanceof ArchiveEntry && $entry->local_post_id() > 0 ) {
				$ids[] = $entry->local_post_id();
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Clean cached page-one membership before it reaches a query argument.
	 *
	 * @param int[] $post_ids Raw stable local post IDs.
	 * @return int[]
	 */
	private function normalise_post_ids( array $post_ids ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	}

	/**
	 * Count eligible local posts without materialising them in PHP.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Page-one local post IDs.
	 * @return int
	 */
	private function count_eligible( ContentSelection $selection, array $excluded_ids ): int {
		$query = new \WP_Query( $this->build_continuation_count_args( $selection, $excluded_ids ) );

		return (int) $query->found_posts;
	}
}
