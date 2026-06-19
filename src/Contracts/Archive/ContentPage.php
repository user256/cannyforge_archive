<?php
/**
 * A single page of whole-database content results.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Archive;

/**
 * Immutable result of a {@see ContentQuery}: the entries on the requested page
 * plus the pagination metadata the front-end needs to render navigation.
 */
final class ContentPage {
	/**
	 * The entries on this page.
	 *
	 * @var ArchiveEntry[]
	 */
	private array $entries;

	/**
	 * Total matching entries across all pages.
	 *
	 * @var int
	 */
	private int $total;

	/**
	 * The 1-based current page number.
	 *
	 * @var int
	 */
	private int $page;

	/**
	 * Page size.
	 *
	 * @var int
	 */
	private int $per_page;

	/**
	 * Construct a result page.
	 *
	 * @param ArchiveEntry[] $entries  Entries on this page.
	 * @param int            $total    Total matching entries.
	 * @param int            $page     1-based current page.
	 * @param int            $per_page Page size.
	 */
	public function __construct( array $entries, int $total, int $page, int $per_page ) {
		$this->entries  = array_values( $entries );
		$this->total    = max( 0, $total );
		$this->page     = max( 1, $page );
		$this->per_page = max( 1, $per_page );
	}

	/**
	 * The entries on this page.
	 *
	 * @return ArchiveEntry[]
	 */
	public function entries(): array {
		return $this->entries;
	}

	/**
	 * Total matching entries across all pages.
	 *
	 * @return int
	 */
	public function total(): int {
		return $this->total;
	}

	/**
	 * The 1-based current page number.
	 *
	 * @return int
	 */
	public function page(): int {
		return $this->page;
	}

	/**
	 * Page size.
	 *
	 * @return int
	 */
	public function per_page(): int {
		return $this->per_page;
	}

	/**
	 * Total number of pages (at least 1).
	 *
	 * @return int
	 */
	public function total_pages(): int {
		return (int) max( 1, (int) ceil( $this->total / $this->per_page ) );
	}

	/**
	 * Whether a page after this one exists.
	 *
	 * @return bool
	 */
	public function has_next(): bool {
		return $this->page < $this->total_pages();
	}

	/**
	 * Whether a page before this one exists.
	 *
	 * @return bool
	 */
	public function has_prev(): bool {
		return $this->page > 1;
	}
}
