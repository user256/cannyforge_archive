<?php
/**
 * At-rest encryption for stored Google secrets and tokens.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypts/decrypts a secret for option storage.
 *
 * New values are authenticated encryption, stored as `enc2:<base64(algo byte .
 * nonce/iv . [tag] . ciphertext)>`, keyed from the WordPress auth salt:
 * {@see self::ALGO_SODIUM} `sodium_crypto_secretbox` when ext-sodium is
 * available (preferred), else {@see self::ALGO_GCM} AES-256-GCM via OpenSSL.
 * Both are AEAD: tampering or a wrong key makes decryption fail closed
 * instead of silently returning garbage (ticket 605).
 *
 * `decrypt()` still reads the legacy unauthenticated `enc:` (AES-256-CBC) tag
 * and untagged plaintext, and opportunistically re-encrypts/re-stores them
 * under `enc2:` via the optional `$on_migrate` callback the first time they
 * are read successfully.
 *
 * `encrypt()` refuses to hand back a plaintext fallback: when no AEAD backend
 * is available it returns `null` so the caller can refuse to persist the
 * secret rather than ever writing plaintext to `wp_options`.
 */
final class SecretCipher {
	/**
	 * Tag prefix marking an authenticated-encryption (AEAD) value.
	 */
	private const TAG_V2 = 'enc2:';

	/**
	 * Tag prefix marking a legacy unauthenticated AES-256-CBC value.
	 */
	private const TAG_V1 = 'enc:';

	/**
	 * Algorithm byte identifying a `sodium_crypto_secretbox` payload.
	 */
	private const ALGO_SODIUM = "\x01";

	/**
	 * Algorithm byte identifying an AES-256-GCM payload.
	 */
	private const ALGO_GCM = "\x02";

	/**
	 * The encryption key (32 raw bytes).
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Construct with a key source string (defaults to the WordPress auth salt).
	 *
	 * @param string|null $salt Key material.
	 */
	public function __construct( ?string $salt = null ) {
		$material  = $salt ?? ( function_exists( 'wp_salt' ) ? (string) wp_salt( 'auth' ) : 'cannyforge-archive-fallback-salt' );
		$this->key = hash( 'sha256', $material, true );
	}

	/**
	 * Whether an AEAD backend is available to {@see self::encrypt()}.
	 *
	 * @return bool
	 */
	public static function backend_available(): bool {
		return function_exists( 'sodium_crypto_secretbox' ) || self::gcm_available();
	}

	/**
	 * Encrypt a plaintext for storage.
	 *
	 * Returns the empty string unchanged (nothing to encrypt). Returns `null`
	 * when no AEAD backend is available and the caller must not persist the
	 * plaintext.
	 *
	 * @param string $plain Plaintext.
	 * @return string|null
	 */
	public function encrypt( string $plain ): ?string {
		if ( '' === $plain ) {
			return $plain;
		}

		if ( function_exists( 'sodium_crypto_secretbox' ) && defined( 'SODIUM_CRYPTO_SECRETBOX_NONCEBYTES' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ct    = sodium_crypto_secretbox( $plain, $nonce, $this->key );

			return self::TAG_V2 . base64_encode( self::ALGO_SODIUM . $nonce . $ct ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary ciphertext must be base64 for option storage.
		}

		if ( self::gcm_available() ) {
			$iv  = random_bytes( 12 );
			$tag = '';
			$ct  = openssl_encrypt( $plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false === $ct || 16 !== strlen( $tag ) ) {
				return null;
			}

			return self::TAG_V2 . base64_encode( self::ALGO_GCM . $iv . $tag . $ct ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary ciphertext must be base64 for option storage.
		}

		return null;
	}

	/**
	 * Decrypt a stored value.
	 *
	 * An `enc2:` value that fails authentication (tampered ciphertext, or a
	 * key that no longer matches — e.g. after a salt rotation) yields ''; it
	 * is never treated as plaintext. A legacy `enc:` or untagged plaintext
	 * value that decrypts/reads successfully is opportunistically
	 * re-encrypted and handed to `$on_migrate` (when given) so the caller can
	 * re-store it under `enc2:`.
	 *
	 * @param string        $stored     Stored value.
	 * @param callable|null $on_migrate Invoked with the re-encrypted `enc2:`
	 *                                  value when a legacy value was migrated:
	 *                                  fn(string $reencrypted): void.
	 * @return string
	 */
	public function decrypt( string $stored, ?callable $on_migrate = null ): string {
		if ( '' === $stored ) {
			return '';
		}

		if ( 0 === strpos( $stored, self::TAG_V2 ) ) {
			return $this->decrypt_v2( substr( $stored, strlen( self::TAG_V2 ) ) ) ?? '';
		}

		if ( 0 === strpos( $stored, self::TAG_V1 ) ) {
			$plain = $this->decrypt_v1( substr( $stored, strlen( self::TAG_V1 ) ) );
			if ( null === $plain ) {
				return '';
			}

			$this->migrate( $plain, $on_migrate );

			return $plain;
		}

		// Untagged legacy plaintext.
		$this->migrate( $stored, $on_migrate );

		return $stored;
	}

	/**
	 * Whether an AES-256-GCM backend is available via OpenSSL.
	 *
	 * @return bool
	 */
	private static function gcm_available(): bool {
		return function_exists( 'openssl_encrypt' )
			&& function_exists( 'openssl_get_cipher_methods' )
			&& in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true );
	}

	/**
	 * Opportunistically re-encrypt and hand a migrated legacy value to the
	 * caller's storage callback. A no-op when there is no callback, nothing
	 * to migrate, or no AEAD backend is available to re-encrypt with (the
	 * legacy value is left in place rather than lost).
	 *
	 * @param string        $plain      Decrypted plaintext.
	 * @param callable|null $on_migrate Migration callback.
	 * @return void
	 */
	private function migrate( string $plain, ?callable $on_migrate ): void {
		if ( null === $on_migrate || '' === $plain ) {
			return;
		}

		$reencrypted = $this->encrypt( $plain );
		if ( null !== $reencrypted ) {
			$on_migrate( $reencrypted );
		}
	}

	/**
	 * Decrypt an `enc2:` payload (base64 of algo byte . algorithm-specific body).
	 *
	 * @param string $encoded Base64 payload following the `enc2:` tag.
	 * @return string|null
	 */
	private function decrypt_v2( string $encoded ): ?string {
		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding our own base64-stored ciphertext.
		if ( false === $raw || '' === $raw ) {
			return null;
		}

		$algo = $raw[0];
		$body = substr( $raw, 1 );

		if ( self::ALGO_SODIUM === $algo ) {
			return $this->decrypt_sodium( $body );
		}

		if ( self::ALGO_GCM === $algo ) {
			return $this->decrypt_gcm( $body );
		}

		return null;
	}

	/**
	 * Decrypt a `sodium_crypto_secretbox` body (nonce . ciphertext+MAC).
	 *
	 * @param string $body Raw nonce + ciphertext.
	 * @return string|null
	 */
	private function decrypt_sodium( string $body ): ?string {
		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) || ! defined( 'SODIUM_CRYPTO_SECRETBOX_NONCEBYTES' ) ) {
			return null;
		}

		$nonce_len = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
		if ( strlen( $body ) <= $nonce_len ) {
			return null;
		}

		$nonce = substr( $body, 0, $nonce_len );
		$ct    = substr( $body, $nonce_len );

		try {
			$plain = sodium_crypto_secretbox_open( $ct, $nonce, $this->key );
		} catch ( \Throwable $e ) {
			return null;
		}

		return false === $plain ? null : $plain;
	}

	/**
	 * Decrypt an AES-256-GCM body (iv[12] . tag[16] . ciphertext).
	 *
	 * @param string $body Raw iv + tag + ciphertext.
	 * @return string|null
	 */
	private function decrypt_gcm( string $body ): ?string {
		if ( ! function_exists( 'openssl_decrypt' ) || strlen( $body ) <= 28 ) {
			return null;
		}

		$iv  = substr( $body, 0, 12 );
		$tag = substr( $body, 12, 16 );
		$ct  = substr( $body, 28 );

		$plain = openssl_decrypt( $ct, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag );

		return is_string( $plain ) ? $plain : null;
	}

	/**
	 * Decrypt a legacy `enc:` (AES-256-CBC, unauthenticated) payload.
	 *
	 * @param string $encoded Base64 payload following the `enc:` tag.
	 * @return string|null
	 */
	private function decrypt_v1( string $encoded ): ?string {
		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding our own base64-stored ciphertext.
		if ( false === $raw || strlen( $raw ) < 17 || ! function_exists( 'openssl_decrypt' ) ) {
			return null;
		}

		$iv  = substr( $raw, 0, 16 );
		$enc = substr( $raw, 16 );
		$dec = openssl_decrypt( $enc, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv );

		return is_string( $dec ) ? $dec : null;
	}
}
