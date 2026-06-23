<?php
/**
 * Tests for the Google secret cipher.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\SecretCipher;
use PHPUnit\Framework\TestCase;

/**
 * Secret values encrypt/decrypt safely for option storage.
 */
class SecretCipherTest extends TestCase {
	/**
	 * Encryption round-trips to the original plaintext.
	 *
	 * @return void
	 */
	public function test_encrypt_then_decrypt_round_trips(): void {
		$cipher = new SecretCipher( 'test-salt' );
		$enc    = $cipher->encrypt( 'top-secret-value' );

		$this->assertNotSame( 'top-secret-value', $enc );
		$this->assertSame( 'top-secret-value', $cipher->decrypt( $enc ) );
	}

	/**
	 * Untagged legacy plaintext decrypts to itself.
	 *
	 * @return void
	 */
	public function test_plaintext_value_decrypts_to_itself(): void {
		$this->assertSame( 'legacy-plain', ( new SecretCipher( 'test-salt' ) )->decrypt( 'legacy-plain' ) );
	}
}
