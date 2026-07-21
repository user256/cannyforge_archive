<?php
/**
 * Content-selection rules for the archive.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inclusion/exclusion rules and pinned ordering applied to the archive entries
 * before rendering, for both News and Blog modes.
 *
 * Immutable and framework-free. Empty include lists mean "no inclusion
 * constraint" (everything passes the include stage); exclude lists drop on any
 * match; pinned URLs are moved to the front in their configured order.
 */
final class ContentSelection {
	/**
	 * Categories an entry must match at least one of (empty = no constraint).
	 *
	 * @var string[]
	 */
	private array $include_categories;

	/**
	 * Tags an entry must match at least one of (empty = no constraint).
	 *
	 * @var string[]
	 */
	private array $include_tags;

	/**
	 * Categories that exclude an entry on any match.
	 *
	 * @var string[]
	 */
	private array $exclude_categories;

	/**
	 * Tags that exclude an entry on any match.
	 *
	 * @var string[]
	 */
	private array $exclude_tags;

	/**
	 * Whether to drop entries whose source is marked noindex.
	 *
	 * @var bool
	 */
	private bool $exclude_noindex;

	/**
	 * URLs pinned to the front of the list, in order.
	 *
	 * @var string[]
	 */
	private array $pinned_urls;

	/**
	 * Construct the content-selection rules.
	 *
	 * @param string[] $include_categories Required-category list.
	 * @param string[] $include_tags       Required-tag list.
	 * @param string[] $exclude_categories Excluded-category list.
	 * @param string[] $exclude_tags       Excluded-tag list.
	 * @param bool     $exclude_noindex    Drop noindex entries.
	 * @param string[] $pinned_urls        Pinned-first URL list.
	 */
	public function __construct(
		array $include_categories = array(),
		array $include_tags = array(),
		array $exclude_categories = array(),
		array $exclude_tags = array(),
		bool $exclude_noindex = false,
		array $pinned_urls = array()
	) {
		$this->include_categories = self::clean( $include_categories );
		$this->include_tags       = self::clean( $include_tags );
		$this->exclude_categories = self::clean( $exclude_categories );
		$this->exclude_tags       = self::clean( $exclude_tags );
		$this->exclude_noindex    = $exclude_noindex;
		$this->pinned_urls        = self::clean( $pinned_urls );
	}

	/**
	 * Required categories (empty = no constraint).
	 *
	 * @return string[]
	 */
	public function include_categories(): array {
		return $this->include_categories;
	}

	/**
	 * Required tags (empty = no constraint).
	 *
	 * @return string[]
	 */
	public function include_tags(): array {
		return $this->include_tags;
	}

	/**
	 * Excluded categories.
	 *
	 * @return string[]
	 */
	public function exclude_categories(): array {
		return $this->exclude_categories;
	}

	/**
	 * Excluded tags.
	 *
	 * @return string[]
	 */
	public function exclude_tags(): array {
		return $this->exclude_tags;
	}

	/**
	 * Whether noindex entries are dropped.
	 *
	 * @return bool
	 */
	public function exclude_noindex(): bool {
		return $this->exclude_noindex;
	}

	/**
	 * Pinned-first URLs, in order.
	 *
	 * @return string[]
	 */
	public function pinned_urls(): array {
		return $this->pinned_urls;
	}

	/**
	 * Build from a raw associative array.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			self::string_list( $data['include_categories'] ?? array() ),
			self::string_list( $data['include_tags'] ?? array() ),
			self::string_list( $data['exclude_categories'] ?? array() ),
			self::string_list( $data['exclude_tags'] ?? array() ),
			(bool) ( $data['exclude_noindex'] ?? false ),
			self::string_list( $data['pinned_urls'] ?? array() )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'include_categories' => $this->include_categories,
			'include_tags'       => $this->include_tags,
			'exclude_categories' => $this->exclude_categories,
			'exclude_tags'       => $this->exclude_tags,
			'exclude_noindex'    => $this->exclude_noindex,
			'pinned_urls'        => $this->pinned_urls,
		);
	}

	/**
	 * Trim, drop empties, and re-index a list of strings already known to be one.
	 *
	 * @param string[] $values Values.
	 * @return string[]
	 */
	private static function clean( array $values ): array {
		$out = array();
		foreach ( $values as $value ) {
			$trimmed = trim( $value );
			if ( '' !== $trimmed ) {
				$out[] = $trimmed;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Coerce a raw value into a clean list of non-empty strings.
	 *
	 * @param mixed $value Raw value (expected to be an array of strings).
	 * @return string[]
	 */
	private static function string_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$strings = array();
		foreach ( $value as $item ) {
			if ( is_string( $item ) ) {
				$strings[] = $item;
			}
		}

		return self::clean( $strings );
	}
}
