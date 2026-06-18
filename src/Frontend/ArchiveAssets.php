<?php
/**
 * Enqueues the front-end archive assets (filters JS + styles).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

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
	 * @param string $base_url Plugin base URL (trailing slash optional).
	 * @param string $version  Plugin version string.
	 */
	public function __construct( string $base_url, string $version ) {
		$this->base_url = rtrim( $base_url, '/' ) . '/';
		$this->version  = $version;
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
		global $wp_query;

		if ( ! isset( $wp_query->query_vars[ ArchivePage::QUERY_VAR ] ) ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$this->base_url . 'assets/css/archive.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$this->base_url . 'assets/js/archive-filters.js',
			array(),
			$this->version,
			true
		);
	}
}
