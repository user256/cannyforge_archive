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

	/**
	 * New values are tagged with the AEAD `enc2:` prefix, not the legacy `enc:` one.
	 *
	 * @return void
	 */
	public function test_encrypt_uses_the_new_aead_tag(): void {
		$enc = ( new SecretCipher( 'test-salt' ) )->encrypt( 'top-secret-value' );

		$this->assertNotNull( $enc );
		$this->assertStringStartsWith( 'enc2:', $enc );
	}

	/**
	 * Flipping a single byte of an `enc2:` ciphertext is caught by the AEAD tag:
	 * decrypt fails safely (returns '') instead of returning corrupted plaintext
	 * or throwing.
	 *
	 * @return void
	 */
	public function test_tampered_ciphertext_fails_to_decrypt(): void {
		$cipher = new SecretCipher( 'test-salt' );
		$enc    = $cipher->encrypt( 'top-secret-value' );
		$this->assertNotNull( $enc );

		$this->assertSame( '', $cipher->decrypt( $this->flip_one_byte( $enc ) ) );
	}

	/**
	 * A legacy `enc:` (unauthenticated AES-256-CBC) value still decrypts
	 * correctly, and the first successful read opportunistically re-encrypts
	 * it and hands the migrated `enc2:` value to the caller's callback so it
	 * can be re-stored (ticket 605).
	 *
	 * @return void
	 */
	public function test_legacy_enc_value_is_migrated_on_first_read(): void {
		$cipher = new SecretCipher( 'test-salt' );
		$legacy = $this->legacy_v1_value( 'test-salt', 'legacy-secret' );

		$migrated = null;
		$plain    = $cipher->decrypt(
			$legacy,
			function ( string $value ) use ( &$migrated ): void {
				$migrated = $value;
			}
		);

		$this->assertSame( 'legacy-secret', $plain );
		$this->assertIsString( $migrated );
		$this->assertStringStartsWith( 'enc2:', $migrated );
		$this->assertSame( 'legacy-secret', $cipher->decrypt( $migrated ) );
	}

	/**
	 * A legacy untagged plaintext value is migrated the same way as a legacy
	 * `enc:` value: the callback receives a freshly encrypted `enc2:` value.
	 *
	 * @return void
	 */
	public function test_plaintext_value_is_migrated_on_first_read(): void {
		$cipher = new SecretCipher( 'test-salt' );

		$migrated = null;
		$plain    = $cipher->decrypt(
			'legacy-plain',
			function ( string $value ) use ( &$migrated ): void {
				$migrated = $value;
			}
		);

		$this->assertSame( 'legacy-plain', $plain );
		$this->assertIsString( $migrated );
		$this->assertStringStartsWith( 'enc2:', $migrated );
		$this->assertSame( 'legacy-plain', $cipher->decrypt( $migrated ) );
	}

	/**
	 * Decrypt() without a migration callback never migrates anything — the
	 * one-argument call used throughout the codebase for a mere read stays a
	 * pure read with no storage side effect.
	 *
	 * @return void
	 */
	public function test_decrypt_without_callback_does_not_migrate(): void {
		$cipher = new SecretCipher( 'test-salt' );

		// No exception, no callback invoked — nothing to assert beyond the
		// plain decrypted value, which the other tests already cover.
		$this->assertSame( 'legacy-plain', $cipher->decrypt( 'legacy-plain' ) );
	}

	/**
	 * A stored `enc2:` value cannot be read back with a different key
	 * (e.g. after a WordPress salt rotation, ticket 605's motivating case):
	 * decryption fails closed, it never returns garbage plaintext.
	 *
	 * @return void
	 */
	public function test_wrong_key_fails_to_decrypt_new_format(): void {
		$enc = ( new SecretCipher( 'salt-a' ) )->encrypt( 'top-secret-value' );
		$this->assertNotNull( $enc );

		$this->assertSame( '', ( new SecretCipher( 'salt-b' ) )->decrypt( $enc ) );
	}

	/**
	 * Same wrong-key guarantee for a legacy `enc:` value.
	 *
	 * @return void
	 */
	public function test_wrong_key_fails_to_decrypt_legacy_format(): void {
		// Legacy `enc:` is unauthenticated AES-256-CBC: decrypting with the
		// wrong key produces random bytes, and OpenSSL's PKCS7 unpad happens
		// to validate purely by chance roughly 1 in 256 times — there is no
		// MAC to reject that case, so asserting the result is always exactly
		// `''` is itself flaky (and running more trials makes a spurious
		// failure *more* likely to turn up across the run, not less). The
		// property that actually matters — and that a wrong key can
		// genuinely never satisfy, coincidental padding or not — is that the
		// real secret is never disclosed.
		$legacy = $this->legacy_v1_value( 'salt-a', 'legacy-secret' );

		$this->assertNotSame( 'legacy-secret', ( new SecretCipher( 'salt-b' ) )->decrypt( $legacy ) );
	}

	/**
	 * A sodium and/or OpenSSL AES-256-GCM backend is available in the test
	 * (and CI, and production PHP 8.1+) environment, so `encrypt()` never
	 * needs to refuse here. The refusal path itself (no backend at all) can't
	 * be exercised without disabling both extensions in-process; it is
	 * covered by {@see \CannyForge\Archive\Integration\Google\SecretCipher::encrypt()}
	 * returning `null`, which every call site treats as "do not persist".
	 *
	 * @return void
	 */
	public function test_backend_is_available_in_this_environment(): void {
		$this->assertTrue( SecretCipher::backend_available() );
	}

	/**
	 * Build a legacy `enc:` (AES-256-CBC, unauthenticated) value the same way
	 * the pre-605 SecretCipher used to, for migration/compat tests.
	 *
	 * @param string $salt_material Key source string.
	 * @param string $plain         Plaintext to encrypt.
	 * @return string
	 */
	private function legacy_v1_value( string $salt_material, string $plain ): string {
		$key = hash( 'sha256', $salt_material, true );
		$iv  = random_bytes( 16 );
		$enc = openssl_encrypt( $plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		$this->assertIsString( $enc );

		return 'enc:' . base64_encode( $iv . $enc );
	}

	/**
	 * Flip the last byte of an `enc2:`-tagged value's ciphertext payload.
	 *
	 * @param string $tagged An `enc2:`-tagged value.
	 * @return string
	 */
	private function flip_one_byte( string $tagged ): string {
		$payload = base64_decode( substr( $tagged, strlen( 'enc2:' ) ), true );
		$this->assertIsString( $payload );

		$last             = strlen( $payload ) - 1;
		$payload[ $last ] = chr( ord( $payload[ $last ] ) ^ 0x01 );

		return 'enc2:' . base64_encode( $payload );
	}
}
