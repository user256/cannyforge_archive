<?php
/**
 * Tests for the Google OAuth client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * The OAuth client refreshes and exchanges tokens via injected HTTP.
 */
class GoogleOauthClientTest extends TestCase {
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
	 * A cached access token is returned without making an HTTP request.
	 *
	 * @return void
	 */
	public function test_access_token_returns_cached_token_when_still_valid(): void {
		$store = new GoogleTokenStore();
		$store->save_access_token( 'cached-access', 5000 );

		$client = new GoogleOauthClient(
			$store,
			'client-id',
			'client-secret',
			static function (): ?array {
				self::fail( 'HTTP should not be called when the cached token is still valid.' );
			}
		);

		$this->assertSame( 'cached-access', $client->access_token( 1000 ) );
	}

	/**
	 * The client refreshes an access token from the stored refresh token.
	 *
	 * @return void
	 */
	public function test_access_token_refreshes_when_cache_is_stale(): void {
		$store = new GoogleTokenStore();
		$store->save_refresh_token( 'refresh-token' );

		$client = new GoogleOauthClient(
			$store,
			'client-id',
			'client-secret',
			static function ( string $url, array $body ): ?array {
				self::assertSame( 'https://oauth2.googleapis.com/token', $url );
				self::assertSame( 'refresh-token', $body['refresh_token'] ?? '' );

				return array(
					'code' => 200,
					'data' => array(
						'access_token' => 'fresh-access',
						'expires_in'   => 3600,
					),
				);
			}
		);

		$this->assertSame( 'fresh-access', $client->access_token( 1000 ) );
		$this->assertSame( GoogleTokenStore::STATUS_CONNECTED, $store->status() );
	}

	/**
	 * The client exchanges an auth code and stores the returned token state.
	 *
	 * @return void
	 */
	public function test_connect_exchanges_authorization_code(): void {
		$store  = new GoogleTokenStore();
		$client = new GoogleOauthClient(
			$store,
			'client-id',
			'client-secret',
			static function ( string $url, array $body ): ?array {
				self::assertSame( 'https://oauth2.googleapis.com/token', $url );
				self::assertSame( 'auth-code', $body['code'] ?? '' );
				self::assertSame( 'https://example.test/callback', $body['redirect_uri'] ?? '' );

				return array(
					'code' => 200,
					'data' => array(
						'access_token'  => 'connected-access',
						'refresh_token' => 'connected-refresh',
						'expires_in'    => 3600,
					),
				);
			}
		);

		$this->assertTrue( $client->connect( 'auth-code', 'https://example.test/callback', 1000 ) );
		$this->assertSame( 'connected-refresh', $store->refresh_token() );
		$this->assertSame( 'connected-access', $store->valid_access_token( 1000 ) );
		$this->assertSame( GoogleTokenStore::STATUS_CONNECTED, $store->status() );
	}

	/**
	 * A failed token response sets an error and leaves the client disconnected.
	 *
	 * @return void
	 */
	public function test_connect_reports_failure_when_google_returns_no_access_token(): void {
		$store  = new GoogleTokenStore();
		$client = new GoogleOauthClient(
			$store,
			'client-id',
			'client-secret',
			static function (): ?array {
				return array(
					'code' => 400,
					'data' => array(
						'error' => 'invalid_grant',
					),
				);
			}
		);

		$this->assertFalse( $client->connect( 'auth-code', 'https://example.test/callback', 1000 ) );
		$this->assertSame( 'Could not complete Google sign-in.', $client->last_error() );
		$this->assertSame( GoogleTokenStore::STATUS_ERROR, $store->status() );
	}
}
