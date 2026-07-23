<?php
/**
 * Tests for the per-site uninstall cleanup (ticket 606).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Bootstrap;

use CannyForge\Archive\Bootstrap\UninstallCleaner;
use CannyForge\Archive\Integration\Google\GoogleRevocationService;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * The cleaner deletes every known option/transient and best-effort revokes
 * the stored Google grant, without depending on a real WordPress runtime.
 */
class UninstallCleanerTest extends TestCase {
	/**
	 * Reset the in-memory stores before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		TransientStore::reset();
	}

	/**
	 * Every option key in the ticket 606 inventory is deleted.
	 *
	 * @return void
	 */
	public function test_clean_current_site_deletes_every_known_option(): void {
		foreach ( UninstallCleaner::option_keys() as $key ) {
			OptionStore::set( $key, 'seeded-value' );
		}

		( new UninstallCleaner( $this->no_op_revocation() ) )->clean_current_site();

		foreach ( UninstallCleaner::option_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, OptionStore::all(), "Option {$key} was not deleted." );
		}
	}

	/**
	 * Every fixed-name archive HTML transient (one per Mode case) is deleted.
	 *
	 * @return void
	 */
	public function test_clean_current_site_deletes_every_known_transient(): void {
		foreach ( UninstallCleaner::transient_keys() as $key ) {
			TransientStore::set( $key, '<p>cached html</p>' );
		}

		( new UninstallCleaner( $this->no_op_revocation() ) )->clean_current_site();

		foreach ( UninstallCleaner::transient_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, TransientStore::all(), "Transient {$key} was not deleted." );
		}
	}

	/**
	 * The inventory covers exactly the option keys the ticket 606 audit
	 * found — a change here should be a deliberate spec update, not a
	 * silent drift.
	 *
	 * @return void
	 */
	public function test_option_keys_match_the_ticket_606_inventory(): void {
		$this->assertSame(
			array(
				'cannyforge_archive_settings',
				'cannyforge_archive_google_settings',
				'cannyforge_archive_google_refresh_token',
				'cannyforge_archive_google_access_token',
				'cannyforge_archive_google_token_expires_at',
				'cannyforge_archive_google_connection_status',
				'cannyforge_archive_google_analytics_scope',
				'cannyforge_archive_google_ga4_cache',
				'cannyforge_archive_google_search_console_cache',
			),
			UninstallCleaner::option_keys()
		);
	}

	/**
	 * The inventory covers exactly the fixed-name transient keys the
	 * ticket 606 audit found.
	 *
	 * @return void
	 */
	public function test_transient_keys_match_the_ticket_606_inventory(): void {
		$this->assertSame(
			array(
				'cannyforge_archive_html_blog',
				'cannyforge_archive_html_news',
				'cannyforge_archive_html_hybrid',
			),
			UninstallCleaner::transient_keys()
		);
	}

	/**
	 * Cleanup calls revoke_and_clear() on the injected revocation service —
	 * the shared best-effort revoke-then-clear path from ticket 614.
	 *
	 * @return void
	 */
	public function test_clean_current_site_revokes_the_stored_google_grant(): void {
		$store = new GoogleTokenStore();
		$store->save_refresh_token( 'refresh-token' );

		$calls      = array();
		$revocation = new GoogleRevocationService(
			$store,
			static function ( string $url, array $body ) use ( &$calls ): ?array {
				$calls[] = array( $url, $body );
				return array( 'code' => 200 );
			}
		);

		( new UninstallCleaner( $revocation ) )->clean_current_site();

		$this->assertCount( 1, $calls, 'Expected exactly one revocation HTTP call.' );
		$this->assertSame( 'refresh-token', $calls[0][1]['token'] ?? '' );
	}

	/**
	 * An unreachable Google revocation endpoint never blocks local cleanup.
	 *
	 * @return void
	 */
	public function test_clean_current_site_still_deletes_local_state_when_revocation_fails(): void {
		OptionStore::set( 'cannyforge_archive_google_refresh_token', 'encrypted-token' );

		$store      = new GoogleTokenStore();
		$revocation = new GoogleRevocationService(
			$store,
			static function (): ?array {
				return null; // Unreachable transport.
			}
		);

		( new UninstallCleaner( $revocation ) )->clean_current_site();

		$this->assertArrayNotHasKey( 'cannyforge_archive_google_refresh_token', OptionStore::all() );
	}

	/**
	 * A revocation service whose remote call always succeeds without
	 * needing to inspect any stored token (nothing to revoke).
	 *
	 * @return GoogleRevocationService
	 */
	private function no_op_revocation(): GoogleRevocationService {
		return new GoogleRevocationService(
			new GoogleTokenStore(),
			static function (): ?array {
				return array( 'code' => 200 );
			}
		);
	}
}
