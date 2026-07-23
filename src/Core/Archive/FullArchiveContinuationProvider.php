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
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Builds later archive pages from all eligible local posts.
 *
 * The promoted first page is deliberately supplied by the caller after its
 * normal filters have run. This means only local post IDs actually rendered on
 * page one are excluded; curated external URLs never suppress a local post.
 */
final class FullArchiveContinuationProvider extends ContentIndexProvider {
	/** Entries per server-rendered continuation page. */
	public const PER_PAGE = 50;

	/** Bounded database batch size while applying PHP-level selection rules. */
	private const BATCH_SIZE = 200;

	/**
	 * Existing selection-rule implementation.
	 *
	 * @var ContentSelector
	 */
	private ContentSelector $selector;

	/**
	 * Construct the continuation provider.
	 *
	 * @param ContentSelector|null $selector Existing selection-rule implementation.
	 */
	public function __construct( ?ContentSelector $selector = null ) {
		$this->selector = $selector ?? new ContentSelector();
	}

	/**
	 * Return one later archive page. Page one here means `/archive/page/2/`.
	 *
	 * @param Settings       $settings       Current settings.
	 * @param ArchiveEntry[] $page_one       Entries actually rendered on `/archive/`.
	 * @param int            $page           Continuation page number, one-based.
	 * @return ContentPage
	 */
	public function provide_continuation( Settings $settings, array $page_one, int $page ): ContentPage {
		return $this->page_entries( $this->eligible_entries( $settings, $this->page_one_post_ids( $page_one ) ), $page_one, $page );
	}

	/**
	 * Make a continuation page from an already ordered eligible list.
	 *
	 * This small pure seam keeps the stable-ID exclusion and page-boundary
	 * behaviour unit-testable; the runtime query has the same IDs in
	 * `post__not_in` as a database-level safeguard.
	 *
	 * @param ArchiveEntry[] $eligible_entries Newest-to-oldest eligible local entries.
	 * @param ArchiveEntry[] $page_one         Entries actually rendered on page one.
	 * @param int            $page             Continuation page number, one-based.
	 * @return ContentPage
	 */
	public function page_entries( array $eligible_entries, array $page_one, int $page ): ContentPage {
		$excluded_ids = $this->page_one_post_ids( $page_one );
		$entries      = array_values(
			array_filter(
				$eligible_entries,
				static fn ( ArchiveEntry $entry ): bool => ! in_array( $entry->local_post_id(), $excluded_ids, true )
			)
		);

		return new ContentPage(
			array_slice( $entries, max( 0, $page - 1 ) * self::PER_PAGE, self::PER_PAGE ),
			count( $entries ),
			$page,
			self::PER_PAGE
		);
	}

	/**
	 * Build the bounded query used for each candidate batch.
	 *
	 * @param int[] $excluded_ids Page-one local post IDs.
	 * @param int   $page         Candidate batch number.
	 * @return array<string, mixed>
	 */
	public function build_continuation_query_args( array $excluded_ids, int $page ): array {
		return array(
			'post_status'         => 'publish',
			'post_type'           => 'post',
			'orderby'             => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'posts_per_page'      => self::BATCH_SIZE,
			'paged'               => max( 1, $page ),
			'post__not_in'        => $excluded_ids,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);
	}

	/**
	 * Extract stable local IDs from entries actually rendered on page one.
	 *
	 * @param ArchiveEntry[] $entries Page-one entries.
	 * @return int[]
	 */
	private function page_one_post_ids( array $entries ): array {
		$ids = array();
		foreach ( $entries as $entry ) {
			if ( $entry instanceof ArchiveEntry && $entry->local_post_id() > 0 ) {
				$ids[] = $entry->local_post_id();
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Query every eligible local post in bounded chronological batches.
	 *
	 * @param Settings $settings     Current settings.
	 * @param int[]    $excluded_ids Page-one local post IDs.
	 * @return ArchiveEntry[]
	 */
	private function eligible_entries( Settings $settings, array $excluded_ids ): array {
		$entries = array();
		$page    = 1;

		do {
			$query = new \WP_Query( $this->build_continuation_query_args( $excluded_ids, $page ) );
			$batch = array();
			foreach ( $query->posts as $post ) {
				if ( $post instanceof \WP_Post ) {
					$batch[] = $this->map_post( $post );
				}
			}
			$batch_count = count( $batch );

			// Keep the database's date/ID order. Pins belong to the curated first
			// page and must not reorder the chronological continuation.
			$entries = array_merge( $entries, $this->selector->filter_entries( $batch, $settings->content_selection() ) );
			++$page;
		} while ( self::BATCH_SIZE === $batch_count );

		return $entries;
	}
}
