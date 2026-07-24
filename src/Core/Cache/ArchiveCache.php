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
 * Caches rendered archive HTML and page-one local-ID membership using the
 * WordPress Transients API.
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
	 * Transient prefix for the stable local post IDs rendered on page one.
	 *
	 * Later full-archive pages need this finite exclusion set, but must not
	 * rebuild and map the promoted entry list on every request to obtain it.
	 */
	private const PAGE_ONE_IDS_PREFIX = 'cannyforge_archive_page_one_ids_';

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
	 * Retrieve the local post IDs actually rendered on the promoted first page.
	 *
	 * @param Settings $settings Current settings.
	 * @return int[]|false Cached IDs, or false on a cache miss.
	 */
	public function get_page_one_post_ids( Settings $settings ): array|false {
		$cached = get_transient( self::page_one_ids_key( $settings ) );
		if ( ! is_array( $cached ) ) {
			return false;
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $cached ) ) ) );
	}

	/**
	 * Store the local post IDs actually rendered on the promoted first page.
	 *
	 * @param Settings $settings Current settings.
	 * @param int[]    $post_ids Stable local post IDs.
	 * @return void
	 */
	public function set_page_one_post_ids( Settings $settings, array $post_ids ): void {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

		set_transient( self::page_one_ids_key( $settings ), $ids, self::TTL );
	}

	/**
	 * Clear every archive HTML and page-one membership transient.
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
			delete_transient( self::PREFIX . $mode->value . '_full' );
			delete_transient( self::PAGE_ONE_IDS_PREFIX . $mode->value );
		}
	}

	/**
	 * Build a stable cache key from the current settings.
	 *
	 * Uses the archive mode as the primary segment so that Blog and News
	 * caches are independent. Full-archive page one (with its continuation
	 * CTA) uses a `_full` suffix so toggling the setting cannot serve the
	 * other mode's HTML even before invalidation runs (ticket 731).
	 *
	 * @param Settings $settings Current settings.
	 * @return string
	 */
	private static function key( Settings $settings ): string {
		$suffix = $settings->full_archive_pagination() ? '_full' : '';

		return self::PREFIX . $settings->mode()->value . $suffix;
	}

	/**
	 * Build the fixed page-one membership key for the current mode.
	 *
	 * Settings/content lifecycle events clear every mode's key together with the
	 * HTML fragment, which is deliberately the same invalidation contract.
	 *
	 * @param Settings $settings Current settings.
	 * @return string
	 */
	private static function page_one_ids_key( Settings $settings ): string {
		return self::PAGE_ONE_IDS_PREFIX . $settings->mode()->value;
	}
}
