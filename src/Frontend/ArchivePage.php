<?php
/**
 * The front-end archive page endpoint.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\ArchiveUrlResolver;
use CannyForge\Archive\Core\Archive\FilterOptionsProvider;
use CannyForge\Archive\Core\Archive\FullArchiveContinuationProvider;
use CannyForge\Archive\Core\Cache\ArchiveCache;

/**
 * Exposes the archive at a stable URL and renders it server-side.
 *
 * Registers a rewrite endpoint (default `/archive/`) backed by a query var, so
 * the archive has a crawlable, no-JavaScript URL. On a matching request it
 * renders the configured entries via the engine renderer. Thin controller: the
 * WordPress rewrite/query ceremony lives here; rendering is the engine's job.
 */
final class ArchivePage {
	/**
	 * The query var that flags an archive request.
	 */
	public const QUERY_VAR = 'cannyforge_archive';

	/**
	 * The default URL slug for the archive endpoint.
	 */
	public const DEFAULT_SLUG = ArchiveUrlResolver::DEFAULT_SLUG;

	/**
	 * Settings persistence.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $repository;

	/**
	 * The entry source.
	 *
	 * @var ArchiveEntryProviderInterface
	 */
	private ArchiveEntryProviderInterface $provider;

	/**
	 * The HTML renderer.
	 *
	 * @var ArchiveRenderer
	 */
	private ArchiveRenderer $renderer;

	/**
	 * The endpoint slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * The HTML fragment cache.
	 *
	 * @var ArchiveCache
	 */
	private ArchiveCache $cache;

	/**
	 * Whole-database filter option source (for the dropdowns).
	 *
	 * @var FilterOptionsProvider
	 */
	private FilterOptionsProvider $options;

	/**
	 * Resolves the canonical archive endpoint URL and the "View Archive"
	 * destination for a non-canonical tail redirect.
	 *
	 * @var ArchiveUrlResolver
	 */
	private ArchiveUrlResolver $url_resolver;

	/**
	 * Optional server-rendered full-archive continuation source.
	 *
	 * @var FullArchiveContinuationProvider
	 */
	private FullArchiveContinuationProvider $continuation;

	/**
	 * Construct the page.
	 *
	 * @param SettingsRepositoryInterface          $repository   Settings persistence.
	 * @param ArchiveEntryProviderInterface        $provider     Entry source.
	 * @param ArchiveRenderer                      $renderer     HTML renderer.
	 * @param string                               $slug         Endpoint slug.
	 * @param ArchiveCache|null                    $cache        HTML fragment cache.
	 * @param FilterOptionsProvider|null           $options      Whole-database filter options.
	 * @param ArchiveUrlResolver|null              $url_resolver Archive URL resolver.
	 * @param FullArchiveContinuationProvider|null $continuation Full-archive continuation source.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		ArchiveEntryProviderInterface $provider,
		ArchiveRenderer $renderer,
		string $slug = self::DEFAULT_SLUG,
		?ArchiveCache $cache = null,
		?FilterOptionsProvider $options = null,
		?ArchiveUrlResolver $url_resolver = null,
		?FullArchiveContinuationProvider $continuation = null
	) {
		$this->repository   = $repository;
		$this->provider     = $provider;
		$this->renderer     = $renderer;
		$this->slug         = '' !== $slug ? $slug : self::DEFAULT_SLUG;
		$this->cache        = $cache ?? new ArchiveCache();
		$this->options      = $options ?? new FilterOptionsProvider();
		$this->url_resolver = $url_resolver ?? new ArchiveUrlResolver( $this->slug );
		$this->continuation = $continuation ?? new FullArchiveContinuationProvider();
	}

	/**
	 * Register the rewrite endpoint and render hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add_rewrite_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ), 1 );
	}

	/**
	 * Register the rewrite endpoint backing the archive URL.
	 *
	 * @return void
	 */
	public function add_rewrite_endpoint(): void {
		add_rewrite_endpoint( $this->slug, EP_ROOT, self::QUERY_VAR );
	}

	/**
	 * Render the archive page when the current request targets the endpoint.
	 *
	 * Renders inside the active theme (`get_header()` / `get_footer()`) so the
	 * output is a valid HTML document, `wp_head` fires (the SEO tags from
	 * {@see SeoHead} are emitted), and the archive inherits theme styling. The
	 * request is forced to a 200 (not a 404) since the endpoint has no post.
	 *
	 * @return void
	 */
	public function maybe_render(): void {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		$settings = $this->repository->get();

		// Ticket 201/612: Canonical-tail rejection/redirect hardening. A
		// non-empty tail (e.g. `/archive/unwanted-tail/`) isn't a page this
		// plugin renders, so send the visitor to the resolved archive
		// destination instead of 200-ing an endpoint variant that doesn't exist.
		$tail = (string) $wp_query->query_vars[ self::QUERY_VAR ];
		if ( '' !== $tail ) {
			$this->maybe_render_continuation( $settings, $tail );
			return;
		}

		$html = $this->build_html( $settings );

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = false;
		}
		status_header( 200 );

		get_header();
		echo '<main id="main" class="site-main" role="main" style="max-width: var(--wp--custom--layout--contentSize, 900px); margin: 0 auto; padding: 2rem 1rem;">';
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes each value.
		echo '</main>';
		get_footer();
		exit;
	}

	/**
	 * Redirect a non-canonical tail request to the resolved archive
	 * destination — {@see ArchiveUrlResolver::destination_url()}, the
	 * `archive_url` override when configured, otherwise this endpoint's own
	 * canonical URL. Never redirects to an empty target: when the resolver
	 * cannot produce one, the request fails closed to a 404 rather than an
	 * unconditional blank-page `exit`.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function redirect_tail( Settings $settings ): void {
		$target = $this->url_resolver->destination_url( $settings );

		if ( '' !== $target ) {
			if ( wp_safe_redirect( esc_url_raw( $target ), 301 ) ) {
				exit;
			}

			// A configured archive_url may be external and rejected by WordPress.
			// Fall back to the endpoint that this plugin owns before failing closed.
			$fallback = $this->url_resolver->endpoint_url();
			if ( '' !== $fallback && $fallback !== $target && wp_safe_redirect( esc_url_raw( $fallback ), 301 ) ) {
				exit;
			}
		}

		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = true;
		}
		status_header( 404 );
	}

	/**
	 * Build the archive HTML, using the cache when warm.
	 *
	 * On a cache miss, queries the provider, applies the entries filter,
	 * fires before/after render actions, renders, and stores the result.
	 *
	 * @param Settings $settings Current settings.
	 * @return string
	 */
	private function build_html( Settings $settings ): string {
		if ( $settings->full_archive_pagination() ) {
			return $this->build_page_one_html( $settings );
		}

		$cached = $this->cache->get( $settings );
		if ( false !== $cached ) {
			return $cached;
		}

		$entries = $this->page_one_entries( $settings );

		$options = $this->filter_options( $settings );

		do_action( 'cannyforge_archive_before_render' );
		$html = $this->renderer->render( $entries, $settings, $options );
		do_action( 'cannyforge_archive_after_render' );
		$this->cache->set( $settings, $html );

		return $html;
	}

	/**
	 * Render page one plus a route to page two when continuation content exists.
	 *
	 * @param Settings $settings Current settings.
	 * @return string
	 */
	private function build_page_one_html( Settings $settings ): string {
		$cached = $this->cache->get( $settings );
		if ( false !== $cached ) {
			return $cached;
		}

		$entries      = $this->page_one_entries( $settings );
		$excluded_ids = $this->continuation->page_one_post_ids( $entries );
		$this->cache->set_page_one_post_ids( $settings, $excluded_ids );
		$has_continuation = $this->continuation->has_continuation( $settings, $excluded_ids );
		$options          = $this->filter_options( $settings );
		do_action( 'cannyforge_archive_before_render' );
		$html = $this->renderer->render( $entries, $settings, $options );
		if ( $has_continuation ) {
			$html .= sprintf(
				'<nav class="cannyforge-archive__pagination" aria-label="%s"><a rel="next" href="%s">%s</a></nav>',
				esc_attr__( 'Archive pages', 'cannyforge-archive' ),
				esc_url( $this->continuation_url( 2 ) ),
				esc_html__( 'Browse the full archive', 'cannyforge-archive' )
			);
		}
		do_action( 'cannyforge_archive_after_render' );
		$this->cache->set( $settings, $html );

		return $html;
	}

	/**
	 * Return page-one entries after the existing extension filter.
	 *
	 * @param Settings $settings Current settings.
	 * @return \CannyForge\Archive\Contracts\Archive\ArchiveEntry[]
	 */
	private function page_one_entries( Settings $settings ): array {
		return apply_filters( 'cannyforge_archive_entries', $this->provider->provide( $settings ) );
	}

	/**
	 * Fetch option lists only for filter dimensions that will be rendered.
	 *
	 * @param Settings $settings Current settings.
	 * @return array<string, array<int, array{value: string, label: string}>>
	 */
	private function filter_options( Settings $settings ): array {
		$filters = $settings->filters();
		$options = array();

		if ( $filters->category() ) {
			$options['category'] = $this->options->categories();
		}
		if ( $filters->tag() ) {
			$options['tag'] = $this->options->tags();
		}
		if ( $filters->author() ) {
			$options['author'] = $this->options->authors();
		}
		if ( $filters->month_year() ) {
			$options['month'] = $this->options->months();
		}

		return $options;
	}

	/**
	 * Recognise and render a valid enabled `/archive/page/N/` tail.
	 *
	 * @param Settings $settings Current settings.
	 * @param string   $tail     Endpoint path after `/archive/`.
	 * @return void
	 */
	private function maybe_render_continuation( Settings $settings, string $tail ): void {
		if ( ! $settings->full_archive_pagination() || ! preg_match( '#^page/([1-9][0-9]*)/?$#', $tail, $matches ) ) {
			$this->redirect_tail( $settings );
			return;
		}

		$requested = (int) $matches[1];
		if ( 1 === $requested ) {
			wp_safe_redirect( esc_url_raw( $this->url_resolver->endpoint_url() ), 301 );
			exit;
		}

		$excluded_ids = $this->cache->get_page_one_post_ids( $settings );
		if ( false === $excluded_ids ) {
			$entries      = $this->page_one_entries( $settings );
			$excluded_ids = $this->continuation->page_one_post_ids( $entries );
			$this->cache->set_page_one_post_ids( $settings, $excluded_ids );
		}

		$page = $this->continuation->provide_continuation( $settings, $excluded_ids, $requested - 1 );
		if ( 0 === $page->total() || $requested - 1 > $page->total_pages() ) {
			global $wp_query;
			if ( $wp_query instanceof \WP_Query ) {
				$wp_query->is_404 = true;
			}
			status_header( 404 );
			return;
		}

		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = false;
		}
		status_header( 200 );
		get_header();
		echo '<main id="main" class="site-main" role="main">';
		echo $this->renderer->render_continuation( $page->entries(), $settings, $requested, $page->total_pages(), fn ( int $number ): string => $this->continuation_url( $number ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</main>';
		get_footer();
		exit;
	}

	/**
	 * Resolve archive page-one or continuation URLs.
	 *
	 * @param int $page Archive URL page number.
	 * @return string
	 */
	private function continuation_url( int $page ): string {
		return 1 === $page ? $this->url_resolver->endpoint_url() : home_url( '/' . $this->slug . '/page/' . $page . '/' );
	}
}
