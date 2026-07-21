<?php
/**
 * A whole-database content search/filter request.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable, framework-free description of one search/filter/pagination request
 * against the whole content database.
 *
 * The archive's default view shows the *promoted* set; as soon as a user
 * searches or filters, the front-end issues one of these against the full
 * database (ticket 301). Carrying the request as a value object keeps the
 * query-building logic in {@see \CannyForge\Archive\Core\Archive\ContentIndexProvider}
 * pure and unit-testable without WordPress.
 */
final class ContentQuery {
	/**
	 * Hard upper bound on page size, so a hostile `per_page` can't exhaust memory.
	 */
	public const MAX_PER_PAGE = 100;

	/**
	 * Free-text search term ('' = no term).
	 *
	 * @var string
	 */
	private string $search;

	/**
	 * Category slug or name filter ('' = no constraint).
	 *
	 * @var string
	 */
	private string $category;

	/**
	 * Tag slug or name filter ('' = no constraint).
	 *
	 * @var string
	 */
	private string $tag;

	/**
	 * Author display name / nicename filter ('' = no constraint).
	 *
	 * @var string
	 */
	private string $author;

	/**
	 * Publication month as `Y-m` ('' = no constraint).
	 *
	 * @var string
	 */
	private string $month;

	/**
	 * 1-based page number.
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
	 * Construct a content query.
	 *
	 * @param string $search   Free-text term.
	 * @param string $category Category slug/name.
	 * @param string $tag      Tag slug/name.
	 * @param string $author   Author label.
	 * @param string $month    Publication month (Y-m).
	 * @param int    $page     1-based page number.
	 * @param int    $per_page Page size.
	 */
	public function __construct(
		string $search = '',
		string $category = '',
		string $tag = '',
		string $author = '',
		string $month = '',
		int $page = 1,
		int $per_page = 20
	) {
		$this->search   = trim( $search );
		$this->category = trim( $category );
		$this->tag      = trim( $tag );
		$this->author   = trim( $author );
		$this->month    = $this->normalise_month( $month );
		$this->page     = max( 1, $page );
		$this->per_page = $this->clamp_per_page( $per_page );
	}

	/**
	 * The search term.
	 *
	 * @return string
	 */
	public function search(): string {
		return $this->search;
	}

	/**
	 * The category filter.
	 *
	 * @return string
	 */
	public function category(): string {
		return $this->category;
	}

	/**
	 * The tag filter.
	 *
	 * @return string
	 */
	public function tag(): string {
		return $this->tag;
	}

	/**
	 * The author filter.
	 *
	 * @return string
	 */
	public function author(): string {
		return $this->author;
	}

	/**
	 * The month filter (Y-m).
	 *
	 * @return string
	 */
	public function month(): string {
		return $this->month;
	}

	/**
	 * The 1-based page number.
	 *
	 * @return int
	 */
	public function page(): int {
		return $this->page;
	}

	/**
	 * The page size.
	 *
	 * @return int
	 */
	public function per_page(): int {
		return $this->per_page;
	}

	/**
	 * Whether this query constrains the result set at all.
	 *
	 * A query with no term and no active filter is "empty" — the front-end shows
	 * the promoted default view rather than issuing a whole-database query.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return '' !== $this->search
			|| '' !== $this->category
			|| '' !== $this->tag
			|| '' !== $this->author
			|| '' !== $this->month;
	}

	/**
	 * Build from a raw associative array (e.g. sanitised request params).
	 *
	 * @param array<string, mixed> $data Raw request data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			self::to_string( $data['search'] ?? '' ),
			self::to_string( $data['category'] ?? '' ),
			self::to_string( $data['tag'] ?? '' ),
			self::to_string( $data['author'] ?? '' ),
			self::to_string( $data['month'] ?? '' ),
			self::to_int( $data['page'] ?? 1, 1 ),
			self::to_int( $data['per_page'] ?? 20, 20 )
		);
	}

	/**
	 * Coerce a raw scalar into a trimmed string, defaulting to empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function to_string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Coerce a raw scalar into an int, using the fallback for non-numeric input.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $fallback Value used when $value is not numeric.
	 * @return int
	 */
	private static function to_int( mixed $value, int $fallback ): int {
		return is_numeric( $value ) ? (int) $value : $fallback;
	}

	/**
	 * Coerce a month to a valid `Y-m` string, or '' when malformed.
	 *
	 * @param string $month Raw month value.
	 * @return string
	 */
	private function normalise_month( string $month ): string {
		$month = trim( $month );

		return 1 === preg_match( '/^\d{4}-\d{2}$/', $month ) ? $month : '';
	}

	/**
	 * Clamp the page size to a sane, bounded range.
	 *
	 * @param int $per_page Requested page size.
	 * @return int
	 */
	private function clamp_per_page( int $per_page ): int {
		if ( $per_page < 1 ) {
			return 20;
		}

		return min( $per_page, self::MAX_PER_PAGE );
	}
}
