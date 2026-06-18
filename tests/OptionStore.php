<?php
/**
 * In-memory option store backing the WordPress options shim.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

/**
 * Static key/value store standing in for the WordPress options table in tests.
 */
final class OptionStore {
	/**
	 * The backing store.
	 *
	 * @var array<string, mixed>
	 */
	private static array $options = array();

	/**
	 * Return all stored options.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return self::$options;
	}

	/**
	 * Set an option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Value to store.
	 * @return void
	 */
	public static function set( string $option, mixed $value ): void {
		self::$options[ $option ] = $value;
	}

	/**
	 * Clear all stored options (call between tests for isolation).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$options = array();
	}
}
