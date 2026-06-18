<?php
/**
 * The front-end archive page endpoint.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;

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
	public const DEFAULT_SLUG = 'archive';

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
	 * Construct the page.
	 *
	 * @param SettingsRepositoryInterface   $repository Settings persistence.
	 * @param ArchiveEntryProviderInterface $provider   Entry source.
	 * @param ArchiveRenderer               $renderer   HTML renderer.
	 * @param string                        $slug       Endpoint slug.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		ArchiveEntryProviderInterface $provider,
		ArchiveRenderer $renderer,
		string $slug = self::DEFAULT_SLUG
	) {
		$this->repository = $repository;
		$this->provider   = $provider;
		$this->renderer   = $renderer;
		$this->slug       = '' !== $slug ? $slug : self::DEFAULT_SLUG;
	}

	/**
	 * Register the rewrite endpoint and render hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add_rewrite_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
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
		$entries  = $this->provider->provide( $settings );

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404 = false;
		}
		status_header( 200 );

		get_header();
		echo $this->renderer->render( $entries, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes each value.
		get_footer();
		exit;
	}
}
