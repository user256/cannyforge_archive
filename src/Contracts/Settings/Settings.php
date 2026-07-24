<?php
/**
 * Plugin settings value object.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable, framework-free snapshot of the plugin configuration.
 *
 * Construction never throws on bad input: {@see self::from_array()} coerces and
 * clamps values to safe ranges so the engine always has a usable configuration.
 * Defaults follow the product brief (see docs/PLAN.md). The raw-value coercion
 * helpers live in {@see SettingsValueCoercion} (split out in ticket 611 to
 * keep this class under the PHPMD length budget).
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
	 * Largest promoted Blog list, matching the bounded News source.
	 */
	private const MAX_BLOG_MAX_URLS = 500;

	/**
	 * Smallest permitted News empty-window fallback count.
	 */
	private const MIN_NEWS_FALLBACK_COUNT = 1;

	/**
	 * Largest permitted News empty-window fallback count.
	 *
	 * Matches {@see \CannyForge\Archive\Core\Archive\NewsEntryProvider::MAX_ENTRIES}
	 * so the fallback can never select more posts than the windowed path.
	 */
	private const MAX_NEWS_FALLBACK_COUNT = 500;

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
	 * How the visible page numbers are selected.
	 *
	 * @var PaginationStyle
	 */
	private PaginationStyle $pagination_style;

	/**
	 * Whether `/archive/page/N/` full-site continuation is enabled.
	 *
	 * @var bool
	 */
	private bool $full_archive_pagination;

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
	 * Blog mode: maximum number of top URLs to include (default 100, max 500).
	 *
	 * @var int
	 */
	private int $blog_max_urls;

	/**
	 * News mode: how many latest posts to show when the recent window is empty
	 * (default 50). See ticket 401 — keeps the promoted surface non-empty.
	 *
	 * @var int
	 */
	private int $news_fallback_count;

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
	 * SEO settings for the archive page.
	 *
	 * @var Seo
	 */
	private Seo $seo;

	/**
	 * Front-end theme settings for the archive and pagination blocks.
	 *
	 * @var Theme
	 */
	private Theme $theme;

	/**
	 * Content-selection rules applied to the entries before rendering.
	 *
	 * @var ContentSelection
	 */
	private ContentSelection $content_selection;

	/**
	 * Construct a settings snapshot.
	 *
	 * @param Mode             $mode              Archive mode.
	 * @param int              $pagination_limit  Pages before the archive link.
	 * @param LinkTypes        $link_types        Entry field toggles.
	 * @param Filters          $filters           Client-side filter toggles.
	 * @param int              $news_window_hours News recent window in hours.
	 * @param int              $blog_max_urls     Blog top-URL cap.
	 * @param string[]         $blog_urls         Blog URL list.
	 * @param Targeting        $targeting         Archive-type targeting toggles.
	 * @param string           $archive_url       "View Archive" link destination override.
	 * @param Seo              $seo               Archive-page SEO settings.
	 * @param Theme            $theme             Front-end theme settings.
	 * @param ContentSelection $content_selection Content-selection rules.
	 * @param int              $news_fallback_count Latest-N count when the News window is empty.
	 * @param PaginationStyle  $pagination_style  How the visible page numbers are selected.
	 * @param bool             $full_archive_pagination Whether later archive pages are enabled.
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
		string $archive_url = '',
		?Seo $seo = null,
		?Theme $theme = null,
		?ContentSelection $content_selection = null,
		int $news_fallback_count = 50,
		PaginationStyle $pagination_style = PaginationStyle::Leading,
		bool $full_archive_pagination = false
	) {
		$this->mode                    = $mode;
		$this->pagination_limit        = max( self::MIN_PAGINATION_LIMIT, $pagination_limit );
		$this->link_types              = $link_types ?? new LinkTypes();
		$this->filters                 = $filters ?? new Filters();
		$this->news_window_hours       = max( self::MIN_NEWS_WINDOW_HOURS, $news_window_hours );
		$this->blog_max_urls           = min( self::MAX_BLOG_MAX_URLS, max( self::MIN_BLOG_MAX_URLS, $blog_max_urls ) );
		$this->blog_urls               = array_slice( array_values( $blog_urls ), 0, $this->blog_max_urls );
		$this->news_fallback_count     = min(
			self::MAX_NEWS_FALLBACK_COUNT,
			max( self::MIN_NEWS_FALLBACK_COUNT, $news_fallback_count )
		);
		$this->targeting               = $targeting ?? new Targeting();
		$this->archive_url             = trim( $archive_url );
		$this->seo                     = $seo ?? new Seo();
		$this->theme                   = $theme ?? new Theme();
		$this->content_selection       = $content_selection ?? new ContentSelection();
		$this->pagination_style        = $pagination_style;
		$this->full_archive_pagination = $full_archive_pagination;
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
	 * How the visible page numbers are selected.
	 *
	 * @return PaginationStyle
	 */
	public function pagination_style(): PaginationStyle {
		return $this->pagination_style;
	}

	/** Whether the optional complete archive continuation is enabled. */
	public function full_archive_pagination(): bool {
		return $this->full_archive_pagination;
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
	 * The archive-page SEO settings.
	 *
	 * @return Seo
	 */
	public function seo(): Seo {
		return $this->seo;
	}

	/**
	 * The front-end theme settings.
	 *
	 * @return Theme
	 */
	public function theme(): Theme {
		return $this->theme;
	}

	/**
	 * The content-selection rules.
	 *
	 * @return ContentSelection
	 */
	public function content_selection(): ContentSelection {
		return $this->content_selection;
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
	 * Latest-N count used when the News recent window is empty (ticket 401).
	 *
	 * @return int
	 */
	public function news_fallback_count(): int {
		return $this->news_fallback_count;
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
		$blog_max_urls = min(
			self::MAX_BLOG_MAX_URLS,
			max( self::MIN_BLOG_MAX_URLS, SettingsValueCoercion::to_int( $data['blog_max_urls'] ?? null, 100 ) )
		);

		return new self(
			Mode::from_value( $data['mode'] ?? null ),
			SettingsValueCoercion::to_int( $data['pagination_limit'] ?? null, 1 ),
			LinkTypes::from_array( SettingsValueCoercion::sub_array( $data, 'link_types' ) ),
			Filters::from_array( SettingsValueCoercion::sub_array( $data, 'filters' ) ),
			SettingsValueCoercion::to_int( $data['news_window_hours'] ?? null, 72 ),
			$blog_max_urls,
			SettingsValueCoercion::string_list( $data['blog_urls'] ?? array(), $blog_max_urls ),
			Targeting::from_array( SettingsValueCoercion::sub_array( $data, 'targeting' ) ),
			SettingsValueCoercion::to_string( $data['archive_url'] ?? null ),
			Seo::from_array( SettingsValueCoercion::sub_array( $data, 'seo' ) ),
			Theme::from_array( SettingsValueCoercion::sub_array( $data, 'theme' ) ),
			ContentSelection::from_array( SettingsValueCoercion::sub_array( $data, 'content_selection' ) ),
			SettingsValueCoercion::to_int( $data['news_fallback_count'] ?? null, 50 ),
			PaginationStyle::from_value( $data['pagination_style'] ?? null ),
			! empty( $data['full_archive_pagination'] )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'mode'                    => $this->mode->value,
			'pagination_limit'        => $this->pagination_limit,
			'pagination_style'        => $this->pagination_style->value,
			'full_archive_pagination' => $this->full_archive_pagination,
			'link_types'              => $this->link_types->to_array(),
			'filters'                 => $this->filters->to_array(),
			'news_window_hours'       => $this->news_window_hours,
			'blog_max_urls'           => $this->blog_max_urls,
			'news_fallback_count'     => $this->news_fallback_count,
			'blog_urls'               => $this->blog_urls,
			'targeting'               => $this->targeting->to_array(),
			'archive_url'             => $this->archive_url,
			'seo'                     => $this->seo->to_array(),
			'theme'                   => $this->theme->to_array(),
			'content_selection'       => $this->content_selection->to_array(),
		);
	}
}
