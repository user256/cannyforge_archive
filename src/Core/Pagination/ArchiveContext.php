<?php
/**
 * A snapshot of the current request's archive-type flags.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Pagination;

/**
 * Immutable, framework-free snapshot of which archive type the current request
 * is (mirroring WordPress's is_category / is_tag / is_author / is_date).
 *
 * Decouples the targeting predicate from the WordPress conditional tags so it
 * can be unit-tested without a WordPress runtime; {@see self::from_wp()} builds
 * one from the live query when WordPress is present.
 */
final class ArchiveContext {
	/**
	 * Whether the request is a category archive.
	 *
	 * @var bool
	 */
	private bool $is_category;

	/**
	 * Whether the request is a tag archive.
	 *
	 * @var bool
	 */
	private bool $is_tag;

	/**
	 * Whether the request is an author archive.
	 *
	 * @var bool
	 */
	private bool $is_author;

	/**
	 * Whether the request is a date archive.
	 *
	 * @var bool
	 */
	private bool $is_date;

	/**
	 * Construct from the four archive-type flags.
	 *
	 * @param bool $is_category Category archive.
	 * @param bool $is_tag      Tag archive.
	 * @param bool $is_author   Author archive.
	 * @param bool $is_date     Date archive.
	 */
	public function __construct(
		bool $is_category = false,
		bool $is_tag = false,
		bool $is_author = false,
		bool $is_date = false
	) {
		$this->is_category = $is_category;
		$this->is_tag      = $is_tag;
		$this->is_author   = $is_author;
		$this->is_date     = $is_date;
	}

	/**
	 * Build a context from the live WordPress conditional tags.
	 *
	 * @return self
	 */
	public static function from_wp(): self {
		return new self(
			is_category(),
			is_tag(),
			is_author(),
			is_date()
		);
	}

	/**
	 * Whether the request is a category archive.
	 *
	 * @return bool
	 */
	public function is_category(): bool {
		return $this->is_category;
	}

	/**
	 * Whether the request is a tag archive.
	 *
	 * @return bool
	 */
	public function is_tag(): bool {
		return $this->is_tag;
	}

	/**
	 * Whether the request is an author archive.
	 *
	 * @return bool
	 */
	public function is_author(): bool {
		return $this->is_author;
	}

	/**
	 * Whether the request is a date archive.
	 *
	 * @return bool
	 */
	public function is_date(): bool {
		return $this->is_date;
	}
}
