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
 * Values are stored as `enc:<base64(iv.ciphertext)>`, keyed from the WordPress
 * auth salt. Untagged values decrypt to themselves so legacy plaintext values
 * degrade safely.
 */
final class SecretCipher {
	/**
	 * Tag prefix marking an encrypted value.
	 */
	private const TAG = 'enc:';

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
	 * Encrypt a plaintext for storage.
	 *
	 * Returns the plaintext unchanged when empty or when OpenSSL is unavailable,
	 * so nothing is silently lost.
	 *
	 * @param string $plain Plaintext.
	 * @return string
	 */
	public function encrypt( string $plain ): string {
		if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) {
			return $plain;
		}

		$iv  = random_bytes( 16 );
		$enc = openssl_encrypt( $plain, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv );
		if ( false === $enc ) {
			return $plain;
		}

		return self::TAG . base64_encode( $iv . $enc ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary ciphertext must be base64 for option storage.
	}

	/**
	 * Decrypt a stored value.
	 *
	 * A value without the `enc:` tag is returned as-is (treated as plaintext); a
	 * tagged value that fails to decrypt yields ''.
	 *
	 * @param string $stored Stored value.
	 * @return string
	 */
	public function decrypt( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}

		if ( 0 !== strpos( $stored, self::TAG ) ) {
			return $stored;
		}

		$raw = base64_decode( substr( $stored, strlen( self::TAG ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding our own base64-stored ciphertext.
		if ( false === $raw || strlen( $raw ) < 17 || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$iv  = substr( $raw, 0, 16 );
		$enc = substr( $raw, 16 );
		$dec = openssl_decrypt( $enc, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv );

		return is_string( $dec ) ? $dec : '';
	}
}
