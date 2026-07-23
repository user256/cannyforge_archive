<?php
/**
 * Per-site uninstall cleanup: options, fixed-name transients, and Google
 * token revocation.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleRevocationService;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Removes every option and fixed-name transient this plugin owns, for the
 * current site, and best-effort revokes the stored Google grant first.
 *
 * Ticket 606: `uninstall.php` is responsible only for the
 * `WP_UNINSTALL_PLUGIN` guard and multisite iteration (`switch_to_blog()`) —
 * see the root `uninstall.php`, which calls {@see self::clean_current_site()}
 * once per site. Keeping the actual cleanup in a class (rather than as
 * procedural top-level code in `uninstall.php`) lets it be unit-tested the
 * same way as every other collaborator in this codebase.
 *
 * Dynamic Google transients are not included in the fixed-name inventory:
 * OAuth CSRF state (`cannyforge_archive_google_oauth_{state}`,
 * {@see \CannyForge\Archive\Admin\GoogleConnectionController}) and the
 * user-scoped Search Console/GA4 property lists carry suffixes, so there is
 * no fixed key to pass to `delete_transient()`. `uninstall.php` deletes all
 * three prefixes directly via one prepared `$wpdb` query instead.
 */
final class UninstallCleaner {
	/**
	 * Every option key the plugin writes via get_option()/update_option().
	 *
	 * Kept in sync with the literal strings used in
	 * src/Core/Settings/OptionsSettingsRepository.php,
	 * src/Integration/Google/GoogleSettingsStore.php,
	 * src/Integration/Google/GoogleTokenStore.php,
	 * src/Integration/Google/Ga4CacheStore.php, and
	 * src/Integration/Google/SearchConsoleCacheStore.php, and
	 * src/Core/Cache/SearchResultCache.php — see ticket 606's
	 * decisions log for the full audit this list is derived from.
	 *
	 * @var string[]
	 */
	private const OPTION_KEYS = array(
		'cannyforge_archive_settings',
		'cannyforge_archive_google_settings',
		'cannyforge_archive_google_refresh_token',
		'cannyforge_archive_google_access_token',
		'cannyforge_archive_google_token_expires_at',
		'cannyforge_archive_google_connection_status',
		'cannyforge_archive_google_analytics_scope',
		'cannyforge_archive_google_ga4_cache',
		'cannyforge_archive_google_search_console_cache',
		'cannyforge_archive_search_cache_generation',
	);

	/**
	 * Fixed-name transients the plugin writes via set_transient(): the
	 * archive HTML fragment cache, one key per
	 * {@see \CannyForge\Archive\Contracts\Settings\Mode} case.
	 *
	 * @var string[]
	 */
	private const TRANSIENT_KEYS = array(
		'cannyforge_archive_html_blog',
		'cannyforge_archive_html_news',
		'cannyforge_archive_html_hybrid',
	);

	/**
	 * Best-effort Google token revocation.
	 *
	 * @var GoogleRevocationService
	 */
	private GoogleRevocationService $revocation;

	/**
	 * Delete-option callable: fn(string $key): void.
	 *
	 * @var callable
	 */
	private $delete_option;

	/**
	 * Delete-transient callable: fn(string $key): void.
	 *
	 * @var callable
	 */
	private $delete_transient;

	/**
	 * Construct the cleaner.
	 *
	 * @param GoogleRevocationService|null $revocation       Token revocation service.
	 * @param callable|null                $delete_option    Delete-option accessor.
	 * @param callable|null                $delete_transient Delete-transient accessor.
	 */
	public function __construct(
		?GoogleRevocationService $revocation = null,
		?callable $delete_option = null,
		?callable $delete_transient = null
	) {
		$this->revocation       = $revocation ?? new GoogleRevocationService( new GoogleTokenStore() );
		$this->delete_option    = $delete_option ?? static function ( string $key ): void {
			if ( function_exists( 'delete_option' ) ) {
				delete_option( $key );
			}
		};
		$this->delete_transient = $delete_transient ?? static function ( string $key ): void {
			if ( function_exists( 'delete_transient' ) ) {
				delete_transient( $key );
			}
		};
	}

	/**
	 * Revoke the stored Google grant (best-effort — a network failure never
	 * blocks local cleanup) and delete every option and fixed-name transient
	 * this plugin owns, for the current site.
	 *
	 * @return void
	 */
	public function clean_current_site(): void {
		$this->revocation->revoke_and_clear();

		foreach ( self::OPTION_KEYS as $key ) {
			( $this->delete_option )( $key );
		}

		foreach ( self::TRANSIENT_KEYS as $key ) {
			( $this->delete_transient )( $key );
		}
	}

	/**
	 * The option keys this cleaner deletes (exposed so tests can verify the
	 * inventory against the ticket 606 spec without duplicating the list).
	 *
	 * @return string[]
	 */
	public static function option_keys(): array {
		return self::OPTION_KEYS;
	}

	/**
	 * The fixed-name transient keys this cleaner deletes.
	 *
	 * @return string[]
	 */
	public static function transient_keys(): array {
		return self::TRANSIENT_KEYS;
	}
}
