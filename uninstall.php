<?php
/**
 * Uninstall routine (ticket 606).
 *
 * Removes the plugin's stored settings and credentials, fixed archive
 * caches, OAuth CSRF state transients, and user-scoped Google property-list
 * transients — and
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

if ( ! function_exists( 'cannyforge_archive_delete_google_dynamic_transients' ) ) {
	/**
	 * Delete Google transient rows whose keys carry a dynamic suffix.
	 *
	 * OAuth state keys are named `cannyforge_archive_google_oauth_{state}` with
	 * a random per-connect-attempt suffix
	 * ({@see \CannyForge\Archive\Admin\GoogleConnectionController}); the
	 * property stores use the same pattern with a WordPress user ID suffix.
	 * There is no fixed key `delete_transient()` can address, so a direct,
	 * prepared `LIKE` query against the current site's options table removes
	 * every row regardless of how many users or connect attempts exist.
	 *
	 * Best-effort: a missing/incompatible `$wpdb` (never the case in a real
	 * WordPress uninstall, but cheap to guard) is a silent no-op rather than
	 * a fatal error.
	 *
	 * @return void
	 */
	function cannyforge_archive_delete_google_dynamic_transients(): void {
		global $wpdb;

		if ( ! ( $wpdb instanceof \wpdb ) ) {
			return;
		}

		$patterns = array();
		foreach ( array(
			'cannyforge_archive_google_oauth_',
			'cannyforge_archive_sc_properties_',
			'cannyforge_archive_ga4_properties_',
		) as $prefix ) {
			$like       = $wpdb->esc_like( $prefix ) . '%';
			$patterns[] = '_transient_' . $like;
			$patterns[] = '_transient_timeout_' . $like;
		}

		$query = $wpdb->prepare(
			'DELETE FROM %i WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s',
			$wpdb->options,
			$patterns[0],
			$patterns[1],
			$patterns[2],
			$patterns[3],
			$patterns[4],
			$patterns[5]
		);
		if ( ! is_string( $query ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Uninstall must sweep dynamically suffixed transients; $query is prepared immediately above, including its identifier.
		$wpdb->query( $query );
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
		cannyforge_archive_delete_google_dynamic_transients();
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
