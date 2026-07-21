<?php
/**
 * Emits the archive page's SEO head tags, interoperating with third-party SEO
 * plugins.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Seo;
use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ArchiveUrlResolver;
use CannyForge\Archive\Core\Seo\CanonicalUrlResolver;
use CannyForge\Archive\Core\Seo\HeadTagBuilder;
use CannyForge\Archive\Core\Seo\SeoProvider;
use CannyForge\Archive\Core\Seo\SeoProviderDetector;

/**
 * Outputs the configured SEO tags in the archive page's `<head>`, on the
 * archive request only, and interoperates with Yoast SEO / Rank Math when
 * either is active (ticket 615).
 *
 * ## Ownership and precedence
 *
 * | Directive         | No SEO plugin active                          | Supported SEO plugin active                               |
 * |--------------------|-----------------------------------------------|-------------------------------------------------------------|
 * | Document title     | Configured title, else theme/site default (`pre_get_document_title`) | Configured title, else the provider's own title (fed through the provider's title filter; `pre_get_document_title` is left untouched) |
 * | Meta description   | Configured description, else omitted           | Configured description, else the provider's own description |
 * | Robots              | Always this plugin's `index`/`follow` settings | Always this plugin's `index`/`follow` settings, fed into the provider's robots filter — archive indexability is a CannyForge-owned setting, not the provider's to guess |
 * | Canonical            | Configured override, else the archive endpoint's own URL ({@see CanonicalUrlResolver}) | Same resolution, fed into the provider's canonical filter instead of echoed directly |
 *
 * The archive endpoint's `archive_url()` pagination-link destination
 * ("View Archive" link target) is never a candidate for any of the above —
 * see {@see CanonicalUrlResolver}.
 *
 * ## How suppression works
 *
 * When {@see SeoProviderDetector} reports a supported provider, this plugin
 * stops emitting its own `<meta name="robots">`, `<meta name="description">`,
 * and `<link rel="canonical">` tags (the {@see self::FILTER} escape hatch
 * still fires, against an empty base fragment, so a site owner can still
 * force output) and leaves `pre_get_document_title` untouched. Instead it
 * feeds its resolved values into the provider's own public filters
 * (`wpseo_*` for Yoast, `rank_math/frontend/*` for Rank Math) so the single
 * tag the provider emits reflects this plugin's configuration. Those filter
 * callbacks are always registered (registering a filter for a hook nothing
 * calls is a no-op) and gate themselves on the archive request, so they never
 * touch tags on other pages.
 *
 * Ticket 612's ArchiveUrlResolver supplies the common endpoint fallback; the
 * real-WordPress integration harness is still deferred to ticket 603.
 */
final class SeoHead {
	/**
	 * The filter other code can use to override the emitted head tags.
	 *
	 * Applied to the fragment this plugin was about to echo directly into
	 * `wp_head` — an empty string when a supported SEO plugin is active and
	 * this plugin has suppressed its own output (see the class docblock).
	 * Return anything other than the given `$tags` to override it, including
	 * `''` to force silence even with no provider active.
	 *
	 * @example
	 * ```php
	 * add_filter( SeoHead::FILTER, function ( string $tags, SeoProvider $provider ): string {
	 *     // Always suppress, even when this plugin would normally emit tags.
	 *     return '';
	 * }, 10, 2 );
	 * ```
	 */
	public const FILTER = 'cannyforge_archive_seo_head';

	/**
	 * Settings persistence.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $repository;

	/**
	 * The pure head-tag builder.
	 *
	 * @var HeadTagBuilder
	 */
	private HeadTagBuilder $builder;

	/**
	 * The shared canonical URL resolver (see the class docblock's precedence table).
	 *
	 * @var CanonicalUrlResolver
	 */
	private CanonicalUrlResolver $canonical;

	/**
	 * Detects an active, supported third-party SEO plugin.
	 *
	 * @var SeoProviderDetector
	 */
	private SeoProviderDetector $detector;

	/**
	 * Resolves the archive endpoint URL shared with the route, pagination,
	 * admin preview, and SEO surfaces.
	 *
	 * @var ArchiveUrlResolver
	 */
	private ArchiveUrlResolver $url_resolver;

	/**
	 * Construct the controller.
	 *
	 * @param SettingsRepositoryInterface $repository   Settings persistence.
	 * @param HeadTagBuilder              $builder      Head-tag builder.
	 * @param string                      $archive_slug Archive endpoint slug.
	 * @param CanonicalUrlResolver|null   $canonical    Shared canonical URL resolver.
	 * @param SeoProviderDetector|null    $detector     Third-party SEO plugin detector.
	 * @param ArchiveUrlResolver|null     $url_resolver Shared archive endpoint URL resolver.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		HeadTagBuilder $builder,
		string $archive_slug = ArchivePage::DEFAULT_SLUG,
		?CanonicalUrlResolver $canonical = null,
		?SeoProviderDetector $detector = null,
		?ArchiveUrlResolver $url_resolver = null
	) {
		$slug = '' !== $archive_slug ? $archive_slug : ArchivePage::DEFAULT_SLUG;

		$this->repository   = $repository;
		$this->builder      = $builder;
		$this->canonical    = $canonical ?? new CanonicalUrlResolver();
		$this->detector     = $detector ?? new SeoProviderDetector();
		$this->url_resolver = $url_resolver ?? new ArchiveUrlResolver( $slug );
	}

	/**
	 * Register the head-output, document-title, and provider-bridge hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'maybe_render' ) );
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ) );

		// Yoast SEO's public filters. Registering these is a no-op unless Yoast
		// is the plugin actually calling apply_filters() on them.
		add_filter( 'wpseo_title', array( $this, 'filter_provider_title' ) );
		add_filter( 'wpseo_metadesc', array( $this, 'filter_provider_description' ) );
		add_filter( 'wpseo_robots', array( $this, 'filter_provider_robots_string' ) );
		add_filter( 'wpseo_canonical', array( $this, 'filter_provider_canonical' ) );

		// Rank Math's public filters. Same no-op-unless-active reasoning.
		add_filter( 'rank_math/frontend/title', array( $this, 'filter_provider_title' ) );
		add_filter( 'rank_math/frontend/description', array( $this, 'filter_provider_description' ) );
		add_filter( 'rank_math/frontend/robots', array( $this, 'filter_provider_robots_array' ) );
		add_filter( 'rank_math/frontend/canonical', array( $this, 'filter_provider_canonical' ) );
	}

	/**
	 * Override the document title on the archive page with the configured title.
	 *
	 * Returns the existing title unchanged off the archive, when no archive
	 * title is configured (so the theme/site default still applies), or when a
	 * supported SEO plugin is active (which owns the title via its own filter
	 * instead — see {@see self::filter_provider_title()} — so this method
	 * yields rather than fighting it for `pre_get_document_title`).
	 *
	 * @param string $title The current document title.
	 * @return string
	 */
	public function filter_title( string $title ): string {
		if ( ! $this->is_archive_request() || SeoProvider::None !== $this->detector->detect() ) {
			return $title;
		}

		$configured = $this->repository->get()->seo()->title();

		return '' !== $configured ? $configured : $title;
	}

	/**
	 * Emit the SEO tags when the current request is the archive page.
	 *
	 * The `<title>` is handled by {@see self::filter_title()} (so the theme owns
	 * one canonical title tag), so the head fragment here is title-less. When a
	 * supported SEO plugin is active, the fragment is suppressed (empty) since
	 * that provider now emits robots/description/canonical, fed this plugin's
	 * values via its own filters — see the class docblock.
	 *
	 * @return void
	 */
	public function maybe_render(): void {
		if ( ! $this->is_archive_request() ) {
			return;
		}

		$provider = $this->detector->detect();
		$tags     = SeoProvider::None === $provider
			? $this->builder->build(
				$this->repository->get()->seo(),
				$this->endpoint_url(),
				false
			)
			: '';

		/**
		 * Filter the archive's SEO head markup before output.
		 *
		 * @param string      $tags     The built head-tag fragment (empty when a
		 *                              supported SEO plugin owns the tags instead).
		 * @param SeoProvider $provider The detected active provider, or {@see SeoProvider::None}.
		 */
		// self::FILTER is the prefixed 'cannyforge_archive_seo_head' literal.
		$tags = (string) apply_filters( self::FILTER, $tags, $provider ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder escapes each value.
	}

	/**
	 * Provider bridge: feed the configured title into the active SEO plugin.
	 *
	 * Off the archive request, the provider's own value passes through
	 * unchanged. On the archive request, the configured title wins when set;
	 * otherwise the provider keeps its own default (unlike
	 * {@see self::filter_title()}, this never falls back to the theme/site
	 * default — the provider already computed one).
	 *
	 * @param string $title The provider's own title for the current request.
	 * @return string
	 */
	public function filter_provider_title( string $title ): string {
		if ( ! $this->is_archive_request() ) {
			return $title;
		}

		$configured = $this->repository->get()->seo()->title();

		return '' !== $configured ? $configured : $title;
	}

	/**
	 * Provider bridge: feed the configured meta description into the active
	 * SEO plugin.
	 *
	 * @param string $description The provider's own description for the current request.
	 * @return string
	 */
	public function filter_provider_description( string $description ): string {
		if ( ! $this->is_archive_request() ) {
			return $description;
		}

		$configured = $this->repository->get()->seo()->meta_description();

		return '' !== $configured ? $configured : $description;
	}

	/**
	 * Provider bridge: feed the archive's robots directive (Yoast's string
	 * format) into the active SEO plugin.
	 *
	 * Always this plugin's value on the archive request — archive
	 * indexability is a CannyForge-owned setting, not the provider's to guess.
	 *
	 * @param string $robots The provider's own robots directive string.
	 * @return string
	 */
	public function filter_provider_robots_string( string $robots ): string {
		return $this->is_archive_request() ? $this->seo()->robots() : $robots;
	}

	/**
	 * Provider bridge: feed the archive's robots directive (Rank Math's array
	 * format) into the active SEO plugin.
	 *
	 * @param array<int|string, mixed> $robots The provider's own robots directive array.
	 * @return array<int|string, mixed>
	 */
	public function filter_provider_robots_array( array $robots ): array {
		if ( ! $this->is_archive_request() ) {
			return $robots;
		}

		return explode( ',', $this->seo()->robots() );
	}

	/**
	 * Provider bridge: feed the resolved canonical URL into the active SEO
	 * plugin.
	 *
	 * Always this plugin's resolution on the archive request (see
	 * {@see CanonicalUrlResolver}) — the archive is the sole authority on its
	 * own canonical identity.
	 *
	 * @param string $canonical The provider's own canonical URL.
	 * @return string
	 */
	public function filter_provider_canonical( string $canonical ): string {
		if ( ! $this->is_archive_request() ) {
			return $canonical;
		}

		return $this->canonical->resolve( $this->seo(), $this->endpoint_url() );
	}

	/**
	 * Whether the current request is the archive endpoint.
	 *
	 * @return bool
	 */
	private function is_archive_request(): bool {
		global $wp_query;

		return isset( $wp_query->query_vars[ ArchivePage::QUERY_VAR ] );
	}

	/**
	 * The current SEO settings.
	 *
	 * @return Seo
	 */
	private function seo(): Seo {
		return $this->repository->get()->seo();
	}

	/**
	 * The archive endpoint's own URL (the canonical fallback; never
	 * {@see \CannyForge\Archive\Contracts\Settings\Settings::archive_url()}).
	 *
	 * @return string
	 */
	private function endpoint_url(): string {
		return $this->url_resolver->endpoint_url();
	}
}
