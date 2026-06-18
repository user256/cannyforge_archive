<?php
/**
 * Archive-type targeting toggles.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

/**
 * Which WordPress archive types the pagination replacement (ticket 107) applies
 * to.
 *
 * Defaults follow the confirmed product decision: Categories on, Tags on,
 * Authors off, Date archives off.
 */
final class Targeting {
	/**
	 * Whether the replacement applies to category archives.
	 *
	 * @var bool
	 */
	private bool $category;

	/**
	 * Whether the replacement applies to tag archives.
	 *
	 * @var bool
	 */
	private bool $tag;

	/**
	 * Whether the replacement applies to author archives.
	 *
	 * @var bool
	 */
	private bool $author;

	/**
	 * Whether the replacement applies to date archives.
	 *
	 * @var bool
	 */
	private bool $date;

	/**
	 * Construct the targeting toggle set.
	 *
	 * @param bool $category Apply to category archives.
	 * @param bool $tag      Apply to tag archives.
	 * @param bool $author   Apply to author archives.
	 * @param bool $date     Apply to date archives.
	 */
	public function __construct(
		bool $category = true,
		bool $tag = true,
		bool $author = false,
		bool $date = false
	) {
		$this->category = $category;
		$this->tag      = $tag;
		$this->author   = $author;
		$this->date     = $date;
	}

	/**
	 * Whether category archives are targeted.
	 *
	 * @return bool
	 */
	public function category(): bool {
		return $this->category;
	}

	/**
	 * Whether tag archives are targeted.
	 *
	 * @return bool
	 */
	public function tag(): bool {
		return $this->tag;
	}

	/**
	 * Whether author archives are targeted.
	 *
	 * @return bool
	 */
	public function author(): bool {
		return $this->author;
	}

	/**
	 * Whether date archives are targeted.
	 *
	 * @return bool
	 */
	public function date(): bool {
		return $this->date;
	}

	/**
	 * Build from a raw associative array, coercing each value to bool.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(bool) ( $data['category'] ?? true ),
			(bool) ( $data['tag'] ?? true ),
			(bool) ( $data['author'] ?? false ),
			(bool) ( $data['date'] ?? false )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array{category: bool, tag: bool, author: bool, date: bool}
	 */
	public function to_array(): array {
		return array(
			'category' => $this->category,
			'tag'      => $this->tag,
			'author'   => $this->author,
			'date'     => $this->date,
		);
	}
}
