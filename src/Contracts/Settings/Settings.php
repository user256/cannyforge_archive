<?php
/**
 * Plugin settings value object.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

/**
 * Immutable, framework-free snapshot of the plugin configuration.
 *
 * Construction never throws on bad input: {@see self::from_array()} coerces and
 * clamps values to safe ranges so the engine always has a usable configuration.
 * Defaults follow the product brief (see docs/PLAN.md).
 */
final class Settings {
	/**
	 * Smallest permitted pagination limit (pages shown before the archive link).
	 */
	private const MIN_PAGINATION_LIMIT = 1;

	/**
	 * Smallest permitted news recent-window, in hours.
	 */
	private const MIN_NEWS_WINDOW_HOURS = 1;

	/**
	 * Smallest permitted Blog top-URL cap.
	 */
	private const MIN_BLOG_MAX_URLS = 1;

	/**
	 * The archive generation mode.
	 *
	 * @var Mode
	 */
	private Mode $mode;

	/**
	 * Pages shown before the "View Archive" link (brief default 1).
	 *
	 * @var int
	 */
	private int $pagination_limit;

	/**
	 * Which fields each archive entry renders.
	 *
	 * @var LinkTypes
	 */
	private LinkTypes $link_types;

	/**
	 * Which client-side filter controls render.
	 *
	 * @var Filters
	 */
	private Filters $filters;

	/**
	 * Which archive types the pagination replacement applies to.
	 *
	 * @var Targeting
	 */
	private Targeting $targeting;

	/**
	 * News mode: recent window in hours (brief default 72).
	 *
	 * @var int
	 */
	private int $news_window_hours;

	/**
	 * Blog mode: maximum number of top URLs to include (brief default 100).
	 *
	 * @var int
	 */
	private int $blog_max_urls;

	/**
	 * Blog mode: the curated list of URLs.
	 *
	 * @var string[]
	 */
	private array $blog_urls;

	/**
	 * Optional "View Archive" link destination override (empty = endpoint URL).
	 *
	 * @var string
	 */
	private string $archive_url;

	/**
	 * Construct a settings snapshot.
	 *
	 * @param Mode      $mode              Archive mode.
	 * @param int       $pagination_limit  Pages before the archive link.
	 * @param LinkTypes $link_types        Entry field toggles.
	 * @param Filters   $filters           Client-side filter toggles.
	 * @param int       $news_window_hours News recent window in hours.
	 * @param int       $blog_max_urls     Blog top-URL cap.
	 * @param string[]  $blog_urls         Blog URL list.
	 * @param Targeting $targeting         Archive-type targeting toggles.
	 * @param string    $archive_url       "View Archive" link destination override.
	 */
	public function __construct(
		Mode $mode = Mode::Blog,
		int $pagination_limit = 1,
		?LinkTypes $link_types = null,
		?Filters $filters = null,
		int $news_window_hours = 72,
		int $blog_max_urls = 100,
		array $blog_urls = array(),
		?Targeting $targeting = null,
		string $archive_url = ''
	) {
		$this->mode              = $mode;
		$this->pagination_limit  = max( self::MIN_PAGINATION_LIMIT, $pagination_limit );
		$this->link_types        = $link_types ?? new LinkTypes();
		$this->filters           = $filters ?? new Filters();
		$this->news_window_hours = max( self::MIN_NEWS_WINDOW_HOURS, $news_window_hours );
		$this->blog_max_urls     = max( self::MIN_BLOG_MAX_URLS, $blog_max_urls );
		$this->blog_urls         = array_values( $blog_urls );
		$this->targeting         = $targeting ?? new Targeting();
		$this->archive_url       = trim( $archive_url );
	}

	/**
	 * The archive mode.
	 *
	 * @return Mode
	 */
	public function mode(): Mode {
		return $this->mode;
	}

	/**
	 * Pages shown before the archive link.
	 *
	 * @return int
	 */
	public function pagination_limit(): int {
		return $this->pagination_limit;
	}

	/**
	 * The entry field toggles.
	 *
	 * @return LinkTypes
	 */
	public function link_types(): LinkTypes {
		return $this->link_types;
	}

	/**
	 * The client-side filter toggles.
	 *
	 * @return Filters
	 */
	public function filters(): Filters {
		return $this->filters;
	}

	/**
	 * The archive-type targeting toggles.
	 *
	 * @return Targeting
	 */
	public function targeting(): Targeting {
		return $this->targeting;
	}

	/**
	 * The "View Archive" link destination override (empty = use endpoint URL).
	 *
	 * @return string
	 */
	public function archive_url(): string {
		return $this->archive_url;
	}

	/**
	 * News recent window in hours.
	 *
	 * @return int
	 */
	public function news_window_hours(): int {
		return $this->news_window_hours;
	}

	/**
	 * Blog top-URL cap.
	 *
	 * @return int
	 */
	public function blog_max_urls(): int {
		return $this->blog_max_urls;
	}

	/**
	 * The Blog URL list, capped at the configured maximum.
	 *
	 * @return string[]
	 */
	public function blog_urls(): array {
		return array_slice( $this->blog_urls, 0, $this->blog_max_urls );
	}

	/**
	 * Build from a raw associative array (e.g. the stored option), coercing and
	 * clamping every value so construction never fails on bad input.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			Mode::from_value( $data['mode'] ?? null ),
			self::to_int( $data['pagination_limit'] ?? null, 1 ),
			LinkTypes::from_array( self::sub_array( $data, 'link_types' ) ),
			Filters::from_array( self::sub_array( $data, 'filters' ) ),
			self::to_int( $data['news_window_hours'] ?? null, 72 ),
			self::to_int( $data['blog_max_urls'] ?? null, 100 ),
			self::string_list( $data['blog_urls'] ?? array() ),
			Targeting::from_array( self::sub_array( $data, 'targeting' ) ),
			self::to_string( $data['archive_url'] ?? null )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'mode'              => $this->mode->value,
			'pagination_limit'  => $this->pagination_limit,
			'link_types'        => $this->link_types->to_array(),
			'filters'           => $this->filters->to_array(),
			'news_window_hours' => $this->news_window_hours,
			'blog_max_urls'     => $this->blog_max_urls,
			'blog_urls'         => $this->blog_urls,
			'targeting'         => $this->targeting->to_array(),
			'archive_url'       => $this->archive_url,
		);
	}

	/**
	 * Read a nested associative array by key, tolerating non-array values.
	 *
	 * @param array<string, mixed> $data Parent data.
	 * @param string               $key  Key to read.
	 * @return array<string, mixed>
	 */
	private static function sub_array( array $data, string $key ): array {
		$value = $data[ $key ] ?? array();
		return is_array( $value ) ? $value : array();
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
	 * Coerce a raw scalar into a trimmed string, defaulting to empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function to_string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
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
			if ( is_string( $item ) && '' !== trim( $item ) ) {
				$strings[] = trim( $item );
			}
		}

		return array_values( array_unique( $strings ) );
	}
}
