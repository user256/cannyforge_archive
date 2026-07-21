<?php
/**
 * Uninstall routine (ticket 606).
 *
 * Removes every row this plugin created — the settings option, the Google
 * client/token options, the GA4 and Search Console caches, the archive HTML
 * fragment-cache transients, and the OAuth CSRF state transients — and
 * best-effort revokes the stored Google grant with Google first, so a stale
 * grant doesn't linger in the site owner's Google account after the plugin
 * is gone.
 *
 * WordPress only executes this file when a plugin is deleted through the
 * Plugins screen (not on deactivation), and only after including it with
 * `WP_UNINSTALL_PLUGIN` defined; the guard below additionally protects
 * against this file ever being requested directly. Deactivating and
 * reactivating the plugin never runs this file — see the deactivation
 * hook in cannyforge-archive.php, which only flushes rewrite rules.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require __DIR__ . '/autoload.php';

use CannyForge\Archive\Bootstrap\UninstallCleaner;

if ( ! function_exists( 'cannyforge_archive_delete_oauth_state_transients' ) ) {
	/**
	 * Delete the Google OAuth CSRF state transient rows directly.
	 *
	 * Each is named `cannyforge_archive_google_oauth_{state}` with a random
	 * per-connect-attempt suffix
	 * ({@see \CannyForge\Archive\Admin\GoogleConnectionController}), so there
	 * is no fixed key `delete_transient()` can address; a direct, prepared
	 * `LIKE` query against the current site's options table is the only way
	 * to remove every row regardless of how many connect attempts were made.
	 *
	 * Best-effort: a missing/incompatible `$wpdb` (never the case in a real
	 * WordPress uninstall, but cheap to guard) is a silent no-op rather than
	 * a fatal error.
	 *
	 * @return void
	 */
	function cannyforge_archive_delete_oauth_state_transients(): void {
		global $wpdb;

		if ( ! ( $wpdb instanceof \wpdb ) ) {
			return;
		}

		$like = $wpdb->esc_like( 'cannyforge_archive_google_oauth_' ) . '%';

		// The table name is a trusted internal $wpdb property (never user
		// input), so it is substituted in after prepare() rather than
		// interpolated into the %s-templated string itself: that keeps the
		// string passed to prepare() a genuine literal, which is what its
		// SQL-injection-safety typing expects.
		$prepared = $wpdb->prepare(
			'DELETE FROM {options_table} WHERE option_name LIKE %s OR option_name LIKE %s',
			'_transient_' . $like,
			'_transient_timeout_' . $like
		);

		if ( null === $prepared ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- both %s values above are already escaped via $wpdb->prepare(); this str_replace() only substitutes the trusted, non-user-controlled $wpdb->options table name into the already-prepared string.
		$wpdb->query( str_replace( '{options_table}', $wpdb->options, $prepared ) );
	}
}

if ( ! function_exists( 'cannyforge_archive_uninstall_clean_current_site' ) ) {
	/**
	 * Clean up everything this plugin owns on the current site.
	 *
	 * @return void
	 */
	function cannyforge_archive_uninstall_clean_current_site(): void {
		( new UninstallCleaner() )->clean_current_site();
		cannyforge_archive_delete_oauth_state_transients();
	}
}

if ( ! function_exists( 'cannyforge_archive_run_uninstall' ) ) {
	/**
	 * Run the cleanup for the current site, or for every site in a
	 * multisite network.
	 *
	 * @return void
	 */
	function cannyforge_archive_run_uninstall(): void {
		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_sites' ) ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				cannyforge_archive_uninstall_clean_current_site();
				restore_current_blog();
			}

			return;
		}

		cannyforge_archive_uninstall_clean_current_site();
	}
}

cannyforge_archive_run_uninstall();
