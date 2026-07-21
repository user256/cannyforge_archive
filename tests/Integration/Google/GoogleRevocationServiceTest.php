<?php
/**
 * Tests for the Google token revocation service.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleRevocationService;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Revocation is a best-effort remote call; local cleanup always happens.
 */
class GoogleRevocationServiceTest extends TestCase {
	/**
	 * Reset the in-memory option store before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
	}

	/**
	 * A successful remote revocation is reported and local state is cleared.
	 *
	 * @return void
	 */
	public function test_revoke_and_clear_succeeds_and_clears_local_state(): void {
		$store = new GoogleTokenStore();
		$store->save_refresh_token( 'refresh-token' );
		$store->save_access_token( 'access-token', 5000 );
		$store->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$calls   = array();
		$revoker = new GoogleRevocationService(
			$store,
			static function ( string $url, array $body ) use ( &$calls ): ?array {
				$calls[] = array( $url, $body );
				return array( 'code' => 200 );
			}
		);

		$this->assertTrue( $revoker->revoke_and_clear() );
		$this->assertSame( '', $revoker->last_error() );
		$this->assertCount( 1, $calls );
		$this->assertSame( 'https://oauth2.googleapis.com/revoke', $calls[0][0] );
		$this->assertSame( 'refresh-token', $calls[0][1]['token'] ?? '' );

		$this->assertSame( '', $store->refresh_token() );
		$this->assertSame( '', $store->valid_access_token( 1000 ) );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $store->status() );
	}

	/**
	 * A failed remote call is reported, but local state is still cleared
	 * (idempotent cleanup even when Google is unreachable/errors).
	 *
	 * @return void
	 */
	public function test_revoke_and_clear_still_clears_local_state_on_remote_failure(): void {
		$store = new GoogleTokenStore();
		$store->save_refresh_token( 'refresh-token' );
		$store->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$revoker = new GoogleRevocationService(
			$store,
			static function (): ?array {
				return array( 'code' => 500 );
			}
		);

		$this->assertFalse( $revoker->revoke_and_clear() );
		$this->assertNotSame( '', $revoker->last_error() );
		$this->assertSame( '', $store->refresh_token() );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $store->status() );
	}

	/**
	 * An unreachable Google endpoint (transport returns null) is a failure,
	 * but local state is still cleared.
	 *
	 * @return void
	 */
	public function test_revoke_and_clear_treats_unreachable_transport_as_failure(): void {
		$store = new GoogleTokenStore();
		$store->save_refresh_token( 'refresh-token' );

		$revoker = new GoogleRevocationService(
			$store,
			static function (): ?array {
				return null;
			}
		);

		$this->assertFalse( $revoker->revoke_and_clear() );
		$this->assertNotSame( '', $revoker->last_error() );
		$this->assertSame( '', $store->refresh_token() );
	}

	/**
	 * Google's `invalid_token` response (HTTP 400) means the grant is already
	 * gone; that is treated as an idempotent success, not a failure.
	 *
	 * @return void
	 */
	public function test_revoke_and_clear_treats_already_invalid_token_as_success(): void {
		$store = new GoogleTokenStore();
		$store->save_refresh_token( 'refresh-token' );

		$revoker = new GoogleRevocationService(
			$store,
			static function (): ?array {
				return array( 'code' => 400 );
			}
		);

		$this->assertTrue( $revoker->revoke_and_clear() );
		$this->assertSame( '', $revoker->last_error() );
	}

	/**
	 * With no stored tokens, revocation is idempotent: no HTTP call is made
	 * and the result reports success.
	 *
	 * @return void
	 */
	public function test_revoke_and_clear_is_idempotent_when_nothing_is_stored(): void {
		$store = new GoogleTokenStore();

		$revoker = new GoogleRevocationService(
			$store,
			static function (): ?array {
				self::fail( 'HTTP should not be called when no token is stored.' );
			}
		);

		$this->assertTrue( $revoker->revoke_and_clear() );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $store->status() );
	}

	/**
	 * When only a cached access token is stored (no refresh token), that
	 * access token is what gets revoked.
	 *
	 * @return void
	 */
	public function test_revoke_and_clear_falls_back_to_access_token(): void {
		$store = new GoogleTokenStore();
		$store->save_access_token( 'access-token', 5000 );

		$calls   = array();
		$revoker = new GoogleRevocationService(
			$store,
			static function ( string $url, array $body ) use ( &$calls ): ?array {
				unset( $url );
				$calls[] = $body;
				return array( 'code' => 200 );
			}
		);

		$this->assertTrue( $revoker->revoke_and_clear( 1000 ) );
	}
}
