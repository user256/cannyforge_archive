<?php
/**
 * Emits the archive page's SEO head tags.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
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
	 * Construct the controller.
	 *
	 * @param SettingsRepositoryInterface $repository   Settings persistence.
	 * @param HeadTagBuilder              $builder      Head-tag builder.
	 * @param string                      $archive_slug Archive endpoint slug.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		HeadTagBuilder $builder,
		string $archive_slug = ArchivePage::DEFAULT_SLUG
	) {
		$this->repository   = $repository;
		$this->builder      = $builder;
		$this->archive_slug = '' !== $archive_slug ? $archive_slug : ArchivePage::DEFAULT_SLUG;
	}

	/**
	 * Register the head-output hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'maybe_render' ) );
	}

	/**
	 * Emit the SEO tags when the current request is the archive page.
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
			home_url( '/' . $this->archive_slug . '/' )
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
