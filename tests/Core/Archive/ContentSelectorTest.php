<?php
/**
 * Tests for the content-selection transform.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\ContentSelection;
use CannyForge\Archive\Core\Archive\ContentSelector;
use PHPUnit\Framework\TestCase;

/**
 * Include/exclude precedence, noindex dropping, and pinned ordering.
 */
class ContentSelectorTest extends TestCase {
	/**
	 * Build an entry with the given URL, categories, tags, and noindex flag.
	 *
	 * @param string   $url        Entry URL.
	 * @param string[] $categories Categories.
	 * @param string[] $tags       Tags.
	 * @param bool     $noindex    Noindex flag.
	 * @return ArchiveEntry
	 */
	private function entry( string $url, array $categories = array(), array $tags = array(), bool $noindex = false ): ArchiveEntry {
		return new ArchiveEntry( $url, $url, '', '', $categories, $tags, '', '', $noindex );
	}

	/**
	 * The URLs of a selection, in order.
	 *
	 * @param ArchiveEntry[] $entries Entries.
	 * @return string[]
	 */
	private function urls( array $entries ): array {
		return array_map( static fn ( ArchiveEntry $entry ): string => $entry->url(), $entries );
	}

	/**
	 * With no rules, every entry passes through in order.
	 *
	 * @return void
	 */
	public function test_no_rules_keeps_everything(): void {
		$entries = array( $this->entry( 'a' ), $this->entry( 'b' ) );

		$result = ( new ContentSelector() )->select( $entries, new ContentSelection() );

		$this->assertSame( array( 'a', 'b' ), $this->urls( $result ) );
	}

	/**
	 * Include rules keep only entries matching at least one included term.
	 *
	 * @return void
	 */
	public function test_include_only(): void {
		$entries = array(
			$this->entry( 'a', array( 'News' ) ),
			$this->entry( 'b', array( 'Sport' ) ),
			$this->entry( 'c', array( 'News', 'Sport' ) ),
		);

		$rules  = new ContentSelection( array( 'News' ) );
		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array( 'a', 'c' ), $this->urls( $result ) );
	}

	/**
	 * Category and tag rules apply only to their own taxonomy, even where a
	 * category and tag share the same visible name.
	 *
	 * @return void
	 */
	public function test_taxonomy_specific_rules_do_not_cross_match_same_named_terms(): void {
		$entries = array(
			$this->entry( 'category', array( 'News' ) ),
			$this->entry( 'tag', array(), array( 'News' ) ),
		);

		$included = ( new ContentSelector() )->select( $entries, new ContentSelection( array( 'News' ) ) );
		$excluded = ( new ContentSelector() )->select( $entries, new ContentSelection( array(), array(), array( 'News' ) ) );

		$this->assertSame( array( 'category' ), $this->urls( $included ) );
		$this->assertSame( array( 'tag' ), $this->urls( $excluded ) );
	}

	/**
	 * Exclude rules drop entries matching any excluded term.
	 *
	 * @return void
	 */
	public function test_exclude_only(): void {
		$entries = array(
			$this->entry( 'a', array( 'News' ) ),
			$this->entry( 'b', array(), array( 'spoiler' ) ),
		);

		$rules  = new ContentSelection( array(), array(), array(), array( 'spoiler' ) );
		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array( 'a' ), $this->urls( $result ) );
	}

	/**
	 * Exclude takes precedence over include for the same entry.
	 *
	 * @return void
	 */
	public function test_exclude_beats_include(): void {
		$entries = array( $this->entry( 'a', array( 'News' ), array( 'spoiler' ) ) );

		// Args: include categories, include tags, exclude categories, exclude tags.
		$rules  = new ContentSelection( array( 'News' ), array(), array(), array( 'spoiler' ) );
		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array(), $this->urls( $result ) );
	}

	/**
	 * The noindex toggle drops noindex entries.
	 *
	 * @return void
	 */
	public function test_drops_noindex_when_enabled(): void {
		$entries = array(
			$this->entry( 'a' ),
			$this->entry( 'b', array(), array(), true ),
		);

		$rules  = new ContentSelection( array(), array(), array(), array(), true );
		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array( 'a' ), $this->urls( $result ) );
	}

	/**
	 * Noindex entries survive when the toggle is off.
	 *
	 * @return void
	 */
	public function test_keeps_noindex_when_disabled(): void {
		$entries = array( $this->entry( 'a', array(), array(), true ) );

		$result = ( new ContentSelector() )->select( $entries, new ContentSelection() );

		$this->assertSame( array( 'a' ), $this->urls( $result ) );
	}

	/**
	 * Pinned URLs move to the front in their configured order; the rest follow.
	 *
	 * @return void
	 */
	public function test_pinned_ordering(): void {
		$entries = array(
			$this->entry( 'a' ),
			$this->entry( 'b' ),
			$this->entry( 'c' ),
		);

		$rules  = new ContentSelection( array(), array(), array(), array(), false, array( 'c', 'a' ) );
		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array( 'c', 'a', 'b' ), $this->urls( $result ) );
	}

	/**
	 * The unpinned filtering variant retains database ordering for the optional
	 * complete archive's chronological continuation.
	 *
	 * @return void
	 */
	public function test_filter_entries_does_not_apply_page_one_pins(): void {
		$entries = array(
			$this->entry( 'newest' ),
			$this->entry( 'older' ),
		);
		$rules   = new ContentSelection( array(), array(), array(), array(), false, array( 'older' ) );

		$result = ( new ContentSelector() )->filter_entries( $entries, $rules );

		$this->assertSame( array( 'newest', 'older' ), $this->urls( $result ) );
	}

	/**
	 * Pinned ordering applies after filtering (a dropped pin does not appear).
	 *
	 * @return void
	 */
	public function test_pinned_after_filtering(): void {
		$entries = array(
			$this->entry( 'a', array( 'News' ) ),
			$this->entry( 'b', array( 'Sport' ) ),
		);

		// Include 'News' drops 'b'; pinning 'b' first has no effect once filtered.
		$rules  = new ContentSelection( array( 'News' ), array(), array(), array(), false, array( 'b', 'a' ) );
		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array( 'a' ), $this->urls( $result ) );
	}

	/**
	 * Case differs but the label is otherwise identical: keep the entry
	 * (aligned with case-insensitive `tax_query` name matching).
	 *
	 * @return void
	 */
	public function test_include_is_case_insensitive(): void {
		$entries = array( $this->entry( 'a', array( 'News' ) ) );
		$rules   = new ContentSelection( array( 'news' ) );

		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array( 'a' ), $this->urls( $result ) );
	}

	/**
	 * Punctuation/spacing differences do not fuzzy-match — same contract as
	 * full-archive `tax_query` `field => name` (ticket 730).
	 *
	 * @return void
	 */
	public function test_include_does_not_strip_punctuation_to_match_slugs(): void {
		$entries = array( $this->entry( 'a', array( 'News & Events' ) ) );
		$rules   = new ContentSelection( array( 'news-events' ) );

		$result = ( new ContentSelector() )->select( $entries, $rules );

		$this->assertSame( array(), $this->urls( $result ) );
	}
}
