<?php
/**
 * Fragment cache for the rendered HTML archive list.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Caches the rendered archive HTML using the WordPress Transients API.
 *
 * The cache key is the archive mode (Blog / News / Hybrid), so each mode
 * caches independently. The cache is not keyed by the full settings; instead
 * it is invalidated by event — {@see CacheInvalidator} clears it on every post
 * save/delete, term and author change, and whenever the plugin settings are
 * saved (`cannyforge_archive_settings_saved`). A configuration change made
 * through the admin UI therefore takes effect immediately; changes written
 * directly to the option (bypassing that action) only take effect on the next
 * invalidating event or after the TTL expires.
 */
final class ArchiveCache {
	/**
	 * Transient prefix.
	 */
	private const PREFIX = 'cannyforge_archive_html_';

	/**
	 * Default TTL: 24 hours.
	 */
	private const TTL = DAY_IN_SECONDS;

	/**
	 * Retrieve cached HTML when present.
	 *
	 * @param Settings $settings Current settings.
	 * @return string|false Cached HTML, or false on miss.
	 */
	public function get( Settings $settings ): string|false {
		$cached = get_transient( self::key( $settings ) );

		return is_string( $cached ) ? $cached : false;
	}

	/**
	 * Store rendered HTML in the transient cache.
	 *
	 * @param Settings $settings Current settings.
	 * @param string   $html     Rendered HTML.
	 * @return void
	 */
	public function set( Settings $settings, string $html ): void {
		set_transient( self::key( $settings ), $html, self::TTL );
	}

	/**
	 * Clear all archive HTML transients.
	 *
	 * WordPress does not support wildcard deletion, so we delete one key per
	 * {@see Mode} case. Iterating the enum (rather than a hand-maintained list
	 * of key strings) means a future mode is covered automatically.
	 *
	 * @return void
	 */
	public function clear(): void {
		foreach ( Mode::cases() as $mode ) {
			delete_transient( self::PREFIX . $mode->value );
		}
	}

	/**
	 * Build a stable cache key from the current settings.
	 *
	 * Uses the archive mode as the primary segment so that Blog and News
	 * caches are independent.
	 *
	 * @param Settings $settings Current settings.
	 * @return string
	 */
	private static function key( Settings $settings ): string {
		return self::PREFIX . $settings->mode()->value;
	}
}
