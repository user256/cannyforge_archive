<?php
/**
 * Archive link-type toggles.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which fields an archive entry renders.
 *
 * Defaults keep the current archive presentation: title, categories, author,
 * and published date on; description, featured image, and tags off.
 */
final class LinkTypes {
	/**
	 * Whether the entry title is shown.
	 *
	 * @var bool
	 */
	private bool $title;

	/**
	 * Whether the entry description is shown.
	 *
	 * @var bool
	 */
	private bool $description;

	/**
	 * Whether the entry featured image is shown.
	 *
	 * @var bool
	 */
	private bool $featured_image;

	/**
	 * Whether the entry categories are shown.
	 *
	 * @var bool
	 */
	private bool $categories;

	/**
	 * Whether the entry tags are shown.
	 *
	 * @var bool
	 */
	private bool $tags;

	/**
	 * Whether the entry author is shown.
	 *
	 * @var bool
	 */
	private bool $author;

	/**
	 * Whether the entry published date is shown.
	 *
	 * @var bool
	 */
	private bool $published_date;

	/**
	 * Construct the toggle set.
	 *
	 * @param bool $title          Show the title.
	 * @param bool $description    Show the description/snippet.
	 * @param bool $featured_image Show the featured image.
	 * @param bool $categories     Show category chips.
	 * @param bool $tags           Show tag chips.
	 * @param bool $author         Show the author chip.
	 * @param bool $published_date Show the published-date chip.
	 */
	public function __construct(
		bool $title = true,
		bool $description = false,
		bool $featured_image = false,
		bool $categories = true,
		bool $tags = false,
		bool $author = true,
		bool $published_date = true
	) {
		$this->title          = $title;
		$this->description    = $description;
		$this->featured_image = $featured_image;
		$this->categories     = $categories;
		$this->tags           = $tags;
		$this->author         = $author;
		$this->published_date = $published_date;
	}

	/**
	 * Whether the title is shown.
	 *
	 * @return bool
	 */
	public function title(): bool {
		return $this->title;
	}

	/**
	 * Whether the description is shown.
	 *
	 * @return bool
	 */
	public function description(): bool {
		return $this->description;
	}

	/**
	 * Whether the featured image is shown.
	 *
	 * @return bool
	 */
	public function featured_image(): bool {
		return $this->featured_image;
	}

	/**
	 * Whether categories are shown.
	 *
	 * @return bool
	 */
	public function categories(): bool {
		return $this->categories;
	}

	/**
	 * Whether tags are shown.
	 *
	 * @return bool
	 */
	public function tags(): bool {
		return $this->tags;
	}

	/**
	 * Whether the author is shown.
	 *
	 * @return bool
	 */
	public function author(): bool {
		return $this->author;
	}

	/**
	 * Whether the published date is shown.
	 *
	 * @return bool
	 */
	public function published_date(): bool {
		return $this->published_date;
	}

	/**
	 * Build from a raw associative array, coercing each value to bool.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(bool) ( $data['title'] ?? true ),
			(bool) ( $data['description'] ?? false ),
			(bool) ( $data['featured_image'] ?? false ),
			(bool) ( $data['categories'] ?? true ),
			(bool) ( $data['tags'] ?? false ),
			(bool) ( $data['author'] ?? true ),
			(bool) ( $data['published_date'] ?? true )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array{title: bool, description: bool, featured_image: bool, categories: bool, tags: bool, author: bool, published_date: bool}
	 */
	public function to_array(): array {
		return array(
			'title'          => $this->title,
			'description'    => $this->description,
			'featured_image' => $this->featured_image,
			'categories'     => $this->categories,
			'tags'           => $this->tags,
			'author'         => $this->author,
			'published_date' => $this->published_date,
		);
	}
}
