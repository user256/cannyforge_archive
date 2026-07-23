<?php
/**
 * In-memory transient store backing the WordPress transients shim.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

/**
 * Static key/value store standing in for the WordPress transients table in tests.
 */
final class TransientStore {
	/**
	 * The backing store.
	 *
	 * @var array<string, mixed>
	 */
	private static array $transients = array();

	/**
	 * Return all stored transients.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return self::$transients;
	}

	/**
	 * Set a transient.
	 *
	 * @param string $transient Transient name.
	 * @param mixed  $value     Value to store.
	 * @return void
	 */
	public static function set( string $transient, mixed $value ): void {
		self::$transients[ $transient ] = $value;
	}

	/**
	 * Delete a transient.
	 *
	 * @param string $transient Transient name.
	 * @return void
	 */
	public static function delete( string $transient ): void {
		unset( self::$transients[ $transient ] );
	}

	/**
	 * Delete every transient whose key starts with a prefix.
	 *
	 * @param string $prefix Transient key prefix.
	 * @return void
	 */
	public static function delete_prefix( string $prefix ): void {
		foreach ( array_keys( self::$transients ) as $key ) {
			if ( str_starts_with( $key, $prefix ) ) {
				unset( self::$transients[ $key ] );
			}
		}
	}

	/**
	 * Clear all stored transients (call between tests for isolation).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$transients = array();
	}
}
