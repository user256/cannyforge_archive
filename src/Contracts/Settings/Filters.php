<?php
/**
 * Client-side filter toggles.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which client-side search/filter controls the archive renders.
 *
 * All client-side per the brief; defaults follow the news-site mock-up
 * (search/category/tag/month+year on, author off).
 */
final class Filters {
	/**
	 * Whether the search box is shown.
	 *
	 * @var bool
	 */
	private bool $search;

	/**
	 * Whether category filters are shown.
	 *
	 * @var bool
	 */
	private bool $category;

	/**
	 * Whether tag filters are shown.
	 *
	 * @var bool
	 */
	private bool $tag;

	/**
	 * Whether month+year filters are shown.
	 *
	 * @var bool
	 */
	private bool $month_year;

	/**
	 * Whether author filters are shown.
	 *
	 * @var bool
	 */
	private bool $author;

	/**
	 * Construct the filter toggle set.
	 *
	 * @param bool $search     Show the search box.
	 * @param bool $category   Show category filters.
	 * @param bool $tag        Show tag filters.
	 * @param bool $month_year Show month+year filters.
	 * @param bool $author     Show author filters.
	 */
	public function __construct(
		bool $search = true,
		bool $category = true,
		bool $tag = true,
		bool $month_year = true,
		bool $author = false
	) {
		$this->search     = $search;
		$this->category   = $category;
		$this->tag        = $tag;
		$this->month_year = $month_year;
		$this->author     = $author;
	}

	/**
	 * Whether the search box is shown.
	 *
	 * @return bool
	 */
	public function search(): bool {
		return $this->search;
	}

	/**
	 * Whether category filters are shown.
	 *
	 * @return bool
	 */
	public function category(): bool {
		return $this->category;
	}

	/**
	 * Whether tag filters are shown.
	 *
	 * @return bool
	 */
	public function tag(): bool {
		return $this->tag;
	}

	/**
	 * Whether month+year filters are shown.
	 *
	 * @return bool
	 */
	public function month_year(): bool {
		return $this->month_year;
	}

	/**
	 * Whether author filters are shown.
	 *
	 * @return bool
	 */
	public function author(): bool {
		return $this->author;
	}

	/**
	 * Build from a raw associative array, coercing each value to bool.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(bool) ( $data['search'] ?? true ),
			(bool) ( $data['category'] ?? true ),
			(bool) ( $data['tag'] ?? true ),
			(bool) ( $data['month_year'] ?? true ),
			(bool) ( $data['author'] ?? false )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array{search: bool, category: bool, tag: bool, month_year: bool, author: bool}
	 */
	public function to_array(): array {
		return array(
			'search'     => $this->search,
			'category'   => $this->category,
			'tag'        => $this->tag,
			'month_year' => $this->month_year,
			'author'     => $this->author,
		);
	}
}
