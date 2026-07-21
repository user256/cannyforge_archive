<?php
/**
 * Tests for the Google connection controller's `disconnect()` flow.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\GoogleRevocationService;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Tests\WpDieException;

/**
 * The disconnect capability/nonce gate and best-effort remote revocation
 * (ticket 614) — see {@see GoogleConnectionControllerTestCase} for the
 * shared fixture.
 */
class GoogleConnectionControllerDisconnectTest extends GoogleConnectionControllerTestCase {
	// -- Capability / nonce gates (ticket 602) ----------------------------------

	/**
	 * `disconnect()` refuses to run for a user lacking the required
	 * capability: local token state is left untouched.
	 *
	 * @return void
	 */
	public function test_disconnect_refused_without_capability(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_refresh_token( 'refresh-token' );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$controller                                  = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore()
		);
		$GLOBALS['cannyforge_test_current_user_can'] = false;

		$this->expectException( WpDieException::class );

		try {
			$controller->disconnect();
		} finally {
			$this->assertSame( 'refresh-token', $tokens->refresh_token() );
			$this->assertSame( GoogleTokenStore::STATUS_CONNECTED, $tokens->status() );
		}
	}

	/**
	 * `disconnect()` refuses to run without a valid nonce: local token state
	 * is left untouched.
	 *
	 * @return void
	 */
	public function test_disconnect_refused_without_valid_nonce(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_refresh_token( 'refresh-token' );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$controller                                     = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore()
		);
		$GLOBALS['cannyforge_test_admin_referer_valid'] = false;

		$this->expectException( WpDieException::class );

		try {
			$controller->disconnect();
		} finally {
			$this->assertSame( 'refresh-token', $tokens->refresh_token() );
			$this->assertSame( GoogleTokenStore::STATUS_CONNECTED, $tokens->status() );
		}
	}

	// -- Disconnect / revocation (AC4) ------------------------------------------

	/**
	 * A successful remote revocation clears local state and reports success.
	 *
	 * @return void
	 */
	public function test_disconnect_revokes_remotely_and_clears_local_state(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_refresh_token( 'refresh-token' );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$search_cache = new SearchConsoleCacheStore();
		$search_cache->save_post_ids( array( 1, 2, 3 ) );

		$revocation = new GoogleRevocationService(
			$tokens,
			static function (): ?array {
				return array( 'code' => 200 );
			}
		);

		$controller = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			$search_cache,
			new Ga4CacheStore(),
			$revocation
		);

		$location = $this->assert_redirects( static fn () => $controller->disconnect() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertSame( '', $tokens->refresh_token() );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
		$this->assertSame( array(), $search_cache->get_post_ids() );
	}

	/**
	 * A failed remote revocation still clears local state (idempotent local
	 * cleanup) but reports the failure to the admin.
	 *
	 * @return void
	 */
	public function test_disconnect_still_clears_local_state_when_remote_revocation_fails(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_refresh_token( 'refresh-token' );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$revocation = new GoogleRevocationService(
			$tokens,
			static function (): ?array {
				return null;
			}
		);

		$controller = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore(),
			$revocation
		);

		$location = $this->assert_redirects( static fn () => $controller->disconnect() );

		$this->assertSame( GoogleConnectionController::NOTICE_ERROR, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertSame( '', $tokens->refresh_token() );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
	}

	/**
	 * Disconnecting when already disconnected (no stored tokens) is
	 * idempotent: no remote call is needed and it still reports success.
	 *
	 * @return void
	 */
	public function test_disconnect_is_idempotent_when_already_disconnected(): void {
		$tokens     = new GoogleTokenStore();
		$revocation = new GoogleRevocationService(
			$tokens,
			static function (): ?array {
				self::fail( 'HTTP should not be called when no token is stored.' );
			}
		);

		$controller = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore(),
			$revocation
		);

		$location = $this->assert_redirects( static fn () => $controller->disconnect() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
	}
}
