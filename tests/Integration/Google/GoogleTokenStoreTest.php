<?php
/**
 * Tests for the dedicated Google token store.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SecretCipher;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Token state is cached separately and securely.
 */
class GoogleTokenStoreTest extends TestCase {
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
	 * Refresh tokens are encrypted at rest and decrypt on read.
	 *
	 * @return void
	 */
	public function test_refresh_token_is_encrypted_at_rest(): void {
		$store = new GoogleTokenStore( new SecretCipher( 'test-salt' ) );
		$store->save_refresh_token( 'refresh-token-value' );

		$this->assertNotSame(
			'refresh-token-value',
			OptionStore::all()['cannyforge_archive_google_refresh_token'] ?? ''
		);
		$this->assertSame( 'refresh-token-value', $store->refresh_token() );
	}

	/**
	 * Access tokens are returned only when they still have at least 90 seconds of life.
	 *
	 * @return void
	 */
	public function test_valid_access_token_uses_safety_buffer(): void {
		$store = new GoogleTokenStore();
		$store->save_access_token( 'cached-token', 1200 );

		$this->assertSame( 'cached-token', $store->valid_access_token( 1000 ) );
		$this->assertSame( '', $store->valid_access_token( 1115 ) );
	}

	/**
	 * Clearing the token store removes the active connection state.
	 *
	 * @return void
	 */
	public function test_clear_resets_tokens_and_status(): void {
		$store = new GoogleTokenStore( new SecretCipher( 'test-salt' ) );
		$store->save_refresh_token( 'refresh-token-value' );
		$store->save_access_token( 'cached-token', 1200 );
		$store->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$store->clear();

		$this->assertSame( '', $store->refresh_token() );
		$this->assertSame( '', $store->valid_access_token( 1000 ) );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $store->status() );
	}
}
