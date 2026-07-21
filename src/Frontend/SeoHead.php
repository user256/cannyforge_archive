<?php
/**
 * Emits the archive page's SEO head tags.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ArchiveUrlResolver;
use CannyForge\Archive\Core\Seo\HeadTagBuilder;

/**
 * Outputs the configured SEO tags in the archive page's `<head>`, on the
 * archive request only.
 *
 * Thin controller: it gates on the archive query var (so it never emits
 * duplicate robots/canonical tags on other pages) and delegates the markup to
 * the pure {@see HeadTagBuilder}. The output passes through a filter so a
 * dedicated SEO plugin can override it; this plugin owns the archive head by
 * default rather than detecting third-party SEO plugins.
 */
final class SeoHead {
	/**
	 * The filter other code can use to override the emitted head tags.
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
	 * The archive endpoint slug (for the canonical fallback URL).
	 *
	 * @var string
	 */
	private string $archive_slug;

	/**
	 * Resolves the archive endpoint's canonical URL — the SEO canonical
	 * fallback used when no `seo.canonical` override is configured.
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
	 * @param ArchiveUrlResolver|null     $url_resolver Archive URL resolver.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		HeadTagBuilder $builder,
		string $archive_slug = ArchivePage::DEFAULT_SLUG,
		?ArchiveUrlResolver $url_resolver = null
	) {
		$this->repository   = $repository;
		$this->builder      = $builder;
		$this->archive_slug = '' !== $archive_slug ? $archive_slug : ArchivePage::DEFAULT_SLUG;
		$this->url_resolver = $url_resolver ?? new ArchiveUrlResolver( $this->archive_slug );
	}

	/**
	 * Register the head-output and document-title hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'maybe_render' ) );
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ) );
	}

	/**
	 * Override the document title on the archive page with the configured title.
	 *
	 * Returns the existing title unchanged off the archive, or when no archive
	 * title is configured — so the theme/site default still applies.
	 *
	 * @param string $title The current document title.
	 * @return string
	 */
	public function filter_title( string $title ): string {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars[ ArchivePage::QUERY_VAR ] ) ) {
			return $title;
		}

		$configured = $this->repository->get()->seo()->title();

		return '' !== $configured ? $configured : $title;
	}

	/**
	 * Emit the SEO tags when the current request is the archive page.
	 *
	 * The `<title>` is handled by {@see self::filter_title()} (so the theme owns
	 * one canonical title tag), so the head fragment here is title-less.
	 *
	 * @return void
	 */
	public function maybe_render(): void {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars[ ArchivePage::QUERY_VAR ] ) ) {
			return;
		}

		$tags = $this->builder->build(
			$this->repository->get()->seo(),
			$this->url_resolver->endpoint_url(),
			false
		);

		/**
		 * Filter the archive's SEO head markup before output.
		 *
		 * @param string $tags The built head-tag fragment.
		 */
		// self::FILTER is the prefixed 'cannyforge_archive_seo_head' literal.
		$tags = (string) apply_filters( self::FILTER, $tags ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builder escapes each value.
	}
}
