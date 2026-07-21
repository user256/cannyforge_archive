<?php
/**
 * Enqueues the front-end archive assets (filters JS + styles).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ThemeCssBuilder;
use CannyForge\Archive\Core\Pagination\ArchiveContext;
use CannyForge\Archive\Core\Pagination\TargetingPredicate;

/**
 * Enqueues the client-side filter script and the front-end stylesheet, only on
 * the archive request.
 *
 * Keeps assets off every other page (the brief: never enqueue globally) by
 * gating on the archive query var, and registers from a file path/URL pair so
 * the plugin stays relocatable. The script is enqueued in the footer and never
 * inlined.
 */
final class ArchiveAssets {
	/**
	 * Handle for the filter script.
	 */
	public const SCRIPT_HANDLE = 'cannyforge-archive-filters';

	/**
	 * Handle for the front-end stylesheet.
	 */
	public const STYLE_HANDLE = 'cannyforge-archive';

	/**
	 * Settings persistence.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $repository;

	/**
	 * Pagination-targeting decision.
	 *
	 * @var TargetingPredicate
	 */
	private TargetingPredicate $predicate;

	/**
	 * Theme CSS builder.
	 *
	 * @var ThemeCssBuilder
	 */
	private ThemeCssBuilder $theme_css;

	/**
	 * Plugin base URL (used to build asset URLs).
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Plugin version (asset cache-buster).
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Construct with the plugin base URL and version.
	 *
	 * @param SettingsRepositoryInterface $repository Settings persistence.
	 * @param TargetingPredicate          $predicate  Pagination-targeting decision.
	 * @param string                      $base_url   Plugin base URL (trailing slash optional).
	 * @param string                      $version    Plugin version string.
	 * @param ThemeCssBuilder|null        $theme_css  Theme CSS builder.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		TargetingPredicate $predicate,
		string $base_url,
		string $version,
		?ThemeCssBuilder $theme_css = null
	) {
		$this->repository = $repository;
		$this->predicate  = $predicate;
		$this->base_url   = rtrim( $base_url, '/' ) . '/';
		$this->version    = $version;
		$this->theme_css  = $theme_css ?? new ThemeCssBuilder();
	}

	/**
	 * Register the enqueue hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the assets when the current request is the archive page.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		$settings = $this->repository->get();

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$this->base_url . 'assets/css/archive.css',
			array(),
			$this->version
		);

		wp_add_inline_style(
			self::STYLE_HANDLE,
			$this->theme_css->build( $settings->theme() )
		);

		if ( $this->is_archive_request() ) {
			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				$this->base_url . 'assets/js/archive-filters.js',
				array(),
				$this->version,
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'cannyforgeArchive',
				array(
					'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
					'action'               => ArchiveSearchEndpoint::ACTION,
					'nonce'                => wp_create_nonce( ArchiveSearchEndpoint::NONCE ),
					'perPage'              => 20,
					'prevLabel'            => __( '‹ Prev', 'cannyforge-archive' ),
					'nextLabel'            => __( 'Next ›', 'cannyforge-archive' ),
					/* translators: {current} and {total} are replaced client-side with page numbers; kept as literal tokens (not sprintf) because the JS layer has no printf-style formatter. */
					'pageStatusTemplate'   => __( 'Page {current} of {total}', 'cannyforge-archive' ),
					'noResultsLabel'       => __( 'No results match your search.', 'cannyforge-archive' ),
					/* translators: %s is replaced client-side with the number of matching results (singular form). */
					'resultsCountSingular' => _n( 'Found %s result across the whole archive', 'Found %s results across the whole archive', 1, 'cannyforge-archive' ),
					/* translators: %s is replaced client-side with the number of matching results (plural form). */
					'resultsCountPlural'   => _n( 'Found %s result across the whole archive', 'Found %s results across the whole archive', 2, 'cannyforge-archive' ),
				)
			);
		}
	}

	/**
	 * Whether the archive stylesheet should load on the current request.
	 *
	 * @return bool
	 */
	private function should_enqueue(): bool {
		return $this->is_archive_request() || $this->predicate->applies(
			$this->repository->get()->targeting(),
			ArchiveContext::from_wp()
		);
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
}
