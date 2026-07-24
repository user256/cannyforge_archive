<?php
/**
 * Filters and orders archive entries by the content-selection rules.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\ContentSelection;

/**
 * Applies the content-selection rules to an entry list: include filters, then
 * exclude filters, then noindex dropping, then pinned-first ordering.
 *
 * Pure and framework-free so the precedence is unit-testable. Operates on the
 * entry list (not the source query), so the same rules apply to both News and
 * Blog providers.
 */
final class ContentSelector {
	/**
	 * Transform the entries into the final, ordered selection.
	 *
	 * @param ArchiveEntry[]   $entries The source entries.
	 * @param ContentSelection $rules   The selection rules.
	 * @return ArchiveEntry[]
	 */
	public function select( array $entries, ContentSelection $rules ): array {
		return $this->pin( $this->filter_entries( $entries, $rules ), $rules->pinned_urls() );
	}

	/**
	 * Apply inclusion, exclusion and noindex rules without changing source order.
	 *
	 * Full-archive continuation uses this variant: unlike the promoted first
	 * page, later pages must remain newest-to-oldest rather than being reordered
	 * by the page-one pinning preference.
	 *
	 * @param ArchiveEntry[]   $entries The source entries.
	 * @param ContentSelection $rules   The selection rules.
	 * @return ArchiveEntry[]
	 */
	public function filter_entries( array $entries, ContentSelection $rules ): array {
		$kept = array();
		foreach ( $entries as $entry ) {
			if ( $this->is_kept( $entry, $rules ) ) {
				$kept[] = $entry;
			}
		}

		return $kept;
	}

	/**
	 * Whether an entry survives the include/exclude/noindex stages.
	 *
	 * @param ArchiveEntry     $entry The entry.
	 * @param ContentSelection $rules The selection rules.
	 * @return bool
	 */
	private function is_kept( ArchiveEntry $entry, ContentSelection $rules ): bool {
		if ( $rules->exclude_noindex() && $entry->is_noindex() ) {
			return false;
		}

		if (
			$this->intersects( $entry->categories(), $rules->exclude_categories() )
			|| $this->intersects( $entry->tags(), $rules->exclude_tags() )
		) {
			return false;
		}

		return (
			array() === $rules->include_categories()
			&& array() === $rules->include_tags()
		)
			|| $this->intersects( $entry->categories(), $rules->include_categories() )
			|| $this->intersects( $entry->tags(), $rules->include_tags() );
	}

	/**
	 * Whether two label lists share at least one value.
	 *
	 * Delegates to {@see TermLabelMatcher} so page-one selection and
	 * full-archive continuation agree (ticket 730).
	 *
	 * @param string[] $a First list.
	 * @param string[] $b Second list.
	 * @return bool
	 */
	private function intersects( array $a, array $b ): bool {
		return TermLabelMatcher::intersects( $a, $b );
	}

	/**
	 * Move pinned URLs to the front, in their configured order.
	 *
	 * Pinned entries that are present keep their relative configured order; the
	 * rest follow in their original order.
	 *
	 * @param ArchiveEntry[] $entries The kept entries.
	 * @param string[]       $pinned  The pinned URLs, in order.
	 * @return ArchiveEntry[]
	 */
	private function pin( array $entries, array $pinned ): array {
		if ( array() === $pinned ) {
			return $entries;
		}

		$front = array();
		foreach ( $pinned as $url ) {
			foreach ( $entries as $entry ) {
				if ( $entry->url() === $url ) {
					$front[] = $entry;
				}
			}
		}

		$rest = array();
		foreach ( $entries as $entry ) {
			if ( ! in_array( $entry->url(), $pinned, true ) ) {
				$rest[] = $entry;
			}
		}

		return array_merge( $front, $rest );
	}
}
