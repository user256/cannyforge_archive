<?php
/**
 * Front-end pagination replacement controller.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Pagination\ArchiveContext;
use CannyForge\Archive\Core\Pagination\PaginationRenderer;
use CannyForge\Archive\Core\Pagination\TargetingPredicate;

/**
 * Replaces the theme's paginated tail with the shortened "View Archive" block
 * on targeted archive listings, and exposes the same block as a shortcode and
 * template tag.
 *
 * Thin controller: it gathers the WordPress query context (current/total pages,
 * archive type, archive URL) and delegates the decision to
 * {@see TargetingPredicate} and the markup to {@see PaginationRenderer}. It is
 * careful not to double-render alongside the theme — on a targeted archive it
 * returns its own block in place of the default; elsewhere it leaves output
 * untouched.
 */
final class PaginationController {
	/**
	 * The shortcode tag exposing the block for explicit placement.
	 */
	public const SHORTCODE = 'cannyforge_pagination';

	/**
	 * Settings persistence.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $repository;

	/**
	 * The targeting decision.
	 *
	 * @var TargetingPredicate
	 */
	private TargetingPredicate $predicate;

	/**
	 * The pure markup renderer.
	 *
	 * @var PaginationRenderer
	 */
	private PaginationRenderer $renderer;

	/**
	 * The archive endpoint slug (for the default "View Archive" destination).
	 *
	 * @var string
	 */
	private string $archive_slug;

	/**
	 * Construct the controller.
	 *
	 * @param SettingsRepositoryInterface $repository   Settings persistence.
	 * @param TargetingPredicate          $predicate    Targeting decision.
	 * @param PaginationRenderer          $renderer     Markup renderer.
	 * @param string                      $archive_slug Archive endpoint slug.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		TargetingPredicate $predicate,
		PaginationRenderer $renderer,
		string $archive_slug = ArchivePage::DEFAULT_SLUG
	) {
		$this->repository   = $repository;
		$this->predicate    = $predicate;
		$this->renderer     = $renderer;
		$this->archive_slug = '' !== $archive_slug ? $archive_slug : ArchivePage::DEFAULT_SLUG;
	}

	/**
	 * Register the pagination filter, shortcode, and template-tag hooks.
	 *
	 * Hooks `navigation_markup_template` — the filter WordPress actually applies to
	 * the pagination wrapper (there is no `the_posts_pagination` markup filter) —
	 * and narrows to the pagination nav by its CSS class.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'navigation_markup_template', array( $this, 'filter_pagination' ), 10, 2 );
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );
	}

	/**
	 * Replace the theme's pagination markup on a targeted archive.
	 *
	 * `navigation_markup_template` fires for several navigation blocks (posts
	 * pagination, post navigation, comments); act only on the posts-pagination
	 * wrapper, and only on a targeted archive. Returning a complete template (with
	 * no `%s` placeholder) replaces the generated links entirely — the crawl-budget
	 * goal — without double-rendering.
	 *
	 * @param string $template  The navigation wrapper template (contains `%s`).
	 * @param string $css_class The navigation block's CSS class.
	 * @return string
	 */
	public function filter_pagination( string $template, string $css_class = '' ): string {
		if ( false === strpos( $css_class, 'pagination' ) || ! $this->applies_here() ) {
			return $template;
		}

		$block = $this->render_block();

		return '' !== $block ? $block : $template;
	}

	/**
	 * Shortcode / template-tag entry point: render the block unconditionally.
	 *
	 * @return string
	 */
	public function shortcode(): string {
		return $this->render_block();
	}

	/**
	 * Whether the replacement applies to the current request.
	 *
	 * @return bool
	 */
	private function applies_here(): bool {
		return $this->predicate->applies(
			$this->repository->get()->targeting(),
			ArchiveContext::from_wp()
		);
	}

	/**
	 * Render the shortened pagination block for the current query.
	 *
	 * @return string
	 */
	private function render_block(): string {
		$settings = $this->repository->get();

		return $this->renderer->render(
			$this->current_page(),
			$this->total_pages(),
			$settings->pagination_limit(),
			$this->archive_url( $settings->archive_url() ),
			__( 'View Archive', 'cannyforge-archive' ),
			static fn ( int $page ): string => (string) get_pagenum_link( $page )
		);
	}

	/**
	 * The current page number (1-based) from the query, clamped to at least 1.
	 *
	 * @return int
	 */
	private function current_page(): int {
		$paged = get_query_var( 'paged', 1 );

		return max( 1, is_numeric( $paged ) ? (int) $paged : 1 );
	}

	/**
	 * The total number of pages in the current query.
	 *
	 * @return int
	 */
	private function total_pages(): int {
		global $wp_query;

		$total = $wp_query->max_num_pages ?? 0;

		return is_numeric( $total ) ? (int) $total : 0;
	}

	/**
	 * Resolve the "View Archive" destination, falling back to the endpoint URL.
	 *
	 * @param string $override Configured destination override (may be empty).
	 * @return string
	 */
	private function archive_url( string $override ): string {
		return '' !== $override ? $override : home_url( '/' . $this->archive_slug . '/' );
	}
}
