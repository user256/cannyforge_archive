<?php
/**
 * Archive-page SEO settings.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The SEO configuration for the archive page itself: title, meta description,
 * robots directives, and a canonical override.
 *
 * Immutable and framework-free. Robots default to index, follow — the archive
 * is meant to be crawled and to pass link equity (the point of the plugin).
 */
final class Seo {
	/**
	 * Archive page title (empty = let the theme/site decide).
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * Archive meta description (empty = omit).
	 *
	 * @var string
	 */
	private string $meta_description;

	/**
	 * Whether the archive is indexable.
	 *
	 * @var bool
	 */
	private bool $index;

	/**
	 * Whether crawlers may follow the archive's links.
	 *
	 * @var bool
	 */
	private bool $follow;

	/**
	 * Canonical URL override (empty = use the archive's own URL).
	 *
	 * @var string
	 */
	private string $canonical;

	/**
	 * Construct the SEO settings.
	 *
	 * @param string $title            Archive title.
	 * @param string $meta_description Meta description.
	 * @param bool   $index            Whether indexable.
	 * @param bool   $follow           Whether links are followable.
	 * @param string $canonical        Canonical URL override.
	 */
	public function __construct(
		string $title = '',
		string $meta_description = '',
		bool $index = true,
		bool $follow = true,
		string $canonical = ''
	) {
		$this->title            = trim( $title );
		$this->meta_description = trim( $meta_description );
		$this->index            = $index;
		$this->follow           = $follow;
		$this->canonical        = trim( $canonical );
	}

	/**
	 * The archive title.
	 *
	 * @return string
	 */
	public function title(): string {
		return $this->title;
	}

	/**
	 * The archive meta description.
	 *
	 * @return string
	 */
	public function meta_description(): string {
		return $this->meta_description;
	}

	/**
	 * Whether the archive is indexable.
	 *
	 * @return bool
	 */
	public function index(): bool {
		return $this->index;
	}

	/**
	 * Whether the archive's links are followable.
	 *
	 * @return bool
	 */
	public function follow(): bool {
		return $this->follow;
	}

	/**
	 * The canonical URL override (empty = archive's own URL).
	 *
	 * @return string
	 */
	public function canonical(): string {
		return $this->canonical;
	}

	/**
	 * The `robots` directive string for the current index/follow state.
	 *
	 * @return string
	 */
	public function robots(): string {
		return ( $this->index ? 'index' : 'noindex' ) . ',' . ( $this->follow ? 'follow' : 'nofollow' );
	}

	/**
	 * Build from a raw associative array, coercing each value.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			self::string( $data['title'] ?? null ),
			self::string( $data['meta_description'] ?? null ),
			(bool) ( $data['index'] ?? true ),
			(bool) ( $data['follow'] ?? true ),
			self::string( $data['canonical'] ?? null )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array{title: string, meta_description: string, index: bool, follow: bool, canonical: string}
	 */
	public function to_array(): array {
		return array(
			'title'            => $this->title,
			'meta_description' => $this->meta_description,
			'index'            => $this->index,
			'follow'           => $this->follow,
			'canonical'        => $this->canonical,
		);
	}

	/**
	 * Coerce a raw scalar into a trimmed string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
