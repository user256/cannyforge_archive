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
	 * Access tokens are encrypted at rest and decrypt on read (ticket 614).
	 *
	 * @return void
	 */
	public function test_access_token_is_encrypted_at_rest(): void {
		$store = new GoogleTokenStore( new SecretCipher( 'test-salt' ) );
		$store->save_access_token( 'access-token-value', 5000 );

		$this->assertNotSame(
			'access-token-value',
			OptionStore::all()['cannyforge_archive_google_access_token'] ?? ''
		);
		$this->assertSame( 'access-token-value', $store->valid_access_token( 1000 ) );
	}

	/**
	 * A pre-614 plaintext access token (no `enc:` tag) still decrypts to
	 * itself, so upgrading sites keep a valid cached token without a
	 * migration step.
	 *
	 * @return void
	 */
	public function test_legacy_plaintext_access_token_still_reads_correctly(): void {
		OptionStore::set( 'cannyforge_archive_google_access_token', 'legacy-plaintext-token' );
		OptionStore::set( 'cannyforge_archive_google_token_expires_at', 5000 );

		$store = new GoogleTokenStore( new SecretCipher( 'test-salt' ) );

		$this->assertSame( 'legacy-plaintext-token', $store->valid_access_token( 1000 ) );
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
