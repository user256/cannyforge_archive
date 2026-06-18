<?php
/**
 * Archive link-type toggles.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

/**
 * Which fields an archive entry renders.
 *
 * Defaults per the product brief: Title on, Description and Featured Image off.
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
	 * Construct the toggle set.
	 *
	 * @param bool $title          Show the title.
	 * @param bool $description    Show the description.
	 * @param bool $featured_image Show the featured image.
	 */
	public function __construct( bool $title = true, bool $description = false, bool $featured_image = false ) {
		$this->title          = $title;
		$this->description    = $description;
		$this->featured_image = $featured_image;
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
	 * Build from a raw associative array, coercing each value to bool.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(bool) ( $data['title'] ?? true ),
			(bool) ( $data['description'] ?? false ),
			(bool) ( $data['featured_image'] ?? false )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array{title: bool, description: bool, featured_image: bool}
	 */
	public function to_array(): array {
		return array(
			'title'          => $this->title,
			'description'    => $this->description,
			'featured_image' => $this->featured_image,
		);
	}
}
