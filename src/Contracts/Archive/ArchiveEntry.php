<?php
/**
 * A single archive entry.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Archive;

/**
 * Immutable, framework-free description of one item in the archive.
 *
 * Carries everything the renderer needs to honour the link-type toggles, plus
 * the filter metadata (categories, tags, author, date) that the client-side
 * filters (ticket 106) read from data-attributes. An entry never assumes which
 * mode produced it.
 */
final class ArchiveEntry {
	/**
	 * The entry URL (required).
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * The entry title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * A short description / excerpt.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Featured image URL, or empty when none.
	 *
	 * @var string
	 */
	private string $featured_image_url;

	/**
	 * Category labels.
	 *
	 * @var string[]
	 */
	private array $categories;

	/**
	 * Tag labels.
	 *
	 * @var string[]
	 */
	private array $tags;

	/**
	 * Author label, or empty when unknown.
	 *
	 * @var string
	 */
	private string $author;

	/**
	 * Publication date as a Y-m-d string, or empty when unknown.
	 *
	 * @var string
	 */
	private string $published_date;

	/**
	 * Construct an entry.
	 *
	 * @param string   $url                The entry URL.
	 * @param string   $title              The entry title.
	 * @param string   $description        A short description.
	 * @param string   $featured_image_url Featured image URL.
	 * @param string[] $categories         Category labels.
	 * @param string[] $tags               Tag labels.
	 * @param string   $author             Author label.
	 * @param string   $published_date     Publication date (Y-m-d).
	 */
	public function __construct(
		string $url,
		string $title = '',
		string $description = '',
		string $featured_image_url = '',
		array $categories = array(),
		array $tags = array(),
		string $author = '',
		string $published_date = ''
	) {
		$this->url                = $url;
		$this->title              = $title;
		$this->description        = $description;
		$this->featured_image_url = $featured_image_url;
		$this->categories         = array_values( $categories );
		$this->tags               = array_values( $tags );
		$this->author             = $author;
		$this->published_date     = $published_date;
	}

	/**
	 * The entry URL.
	 *
	 * @return string
	 */
	public function url(): string {
		return $this->url;
	}

	/**
	 * The entry title (falls back to the URL when empty).
	 *
	 * @return string
	 */
	public function title(): string {
		return '' !== $this->title ? $this->title : $this->url;
	}

	/**
	 * The entry description.
	 *
	 * @return string
	 */
	public function description(): string {
		return $this->description;
	}

	/**
	 * The featured image URL.
	 *
	 * @return string
	 */
	public function featured_image_url(): string {
		return $this->featured_image_url;
	}

	/**
	 * Category labels.
	 *
	 * @return string[]
	 */
	public function categories(): array {
		return $this->categories;
	}

	/**
	 * Tag labels.
	 *
	 * @return string[]
	 */
	public function tags(): array {
		return $this->tags;
	}

	/**
	 * Author label.
	 *
	 * @return string
	 */
	public function author(): string {
		return $this->author;
	}

	/**
	 * Publication date (Y-m-d), or empty.
	 *
	 * @return string
	 */
	public function published_date(): string {
		return $this->published_date;
	}

	/**
	 * The publication month as Y-m, derived from the date, or empty.
	 *
	 * @return string
	 */
	public function published_month(): string {
		return '' !== $this->published_date ? substr( $this->published_date, 0, 7 ) : '';
	}
}
