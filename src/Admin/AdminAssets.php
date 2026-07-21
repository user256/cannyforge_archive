<?php
/**
 * Enqueues the admin settings-page assets.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the CannyForge admin assets, only on the plugin's settings page.
 *
 * Gates on the `admin_enqueue_scripts` hook suffix so the brand styles never
 * load on other admin screens, and registers from a base URL/version pair so the
 * plugin stays relocatable. Never inlined.
 */
final class AdminAssets {
	/**
	 * Handle for the admin stylesheet.
	 */
	public const STYLE_HANDLE = 'cannyforge-archive-admin';

	/**
	 * Handle for the admin script.
	 */
	public const SCRIPT_HANDLE = 'cannyforge-archive-admin';

	/**
	 * The hook suffix of the plugin's top-level menu page.
	 */
	private const PAGE_HOOK = 'toplevel_page_' . SettingsPage::PAGE_SLUG;

	/**
	 * Plugin base URL.
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
	 * Register the admin enqueue hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the admin assets on the plugin's settings page only.
	 *
	 * @param string $hook_suffix The current admin page's hook suffix.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( self::PAGE_HOOK !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$this->base_url . 'assets/css/admin.css',
			array(),
			$this->asset_version( 'assets/css/admin.css' )
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$this->base_url . 'assets/js/admin.js',
			array(),
			$this->asset_version( 'assets/js/admin.js' ),
			true
		);
	}

	/**
	 * Cache-busting version for a plugin asset.
	 *
	 * Appends the local file mtime when available so repeated local reinstalls do
	 * not keep serving stale admin CSS/JS under the same plugin version.
	 *
	 * @param string $relative_path Asset path relative to the plugin root.
	 * @return string
	 */
	private function asset_version( string $relative_path ): string {
		if ( defined( 'CANNYFORGE_ARCHIVE_FILE' ) ) {
			$absolute = dirname( CANNYFORGE_ARCHIVE_FILE ) . '/' . ltrim( $relative_path, '/' );

			if ( file_exists( $absolute ) ) {
				$mtime = filemtime( $absolute );

				if ( false !== $mtime ) {
					return $this->version . '.' . (string) $mtime;
				}
			}
		}

		return $this->version;
	}
}
