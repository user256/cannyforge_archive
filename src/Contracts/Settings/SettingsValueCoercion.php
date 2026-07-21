<?php
/**
 * Static coercion helpers used when building a Settings snapshot from raw
 * stored data.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure, stateless value coercion shared by {@see Settings::from_array()}.
 *
 * Split out of Settings (ticket 611) to keep the value object itself under
 * the PHPMD length budget. Like the rest of Contracts\Settings, this owns no
 * admin UI or engine logic.
 */
final class SettingsValueCoercion {
	/**
	 * Read a nested associative array by key, tolerating non-array values.
	 *
	 * @param array<string, mixed> $data Parent data.
	 * @param string               $key  Key to read.
	 * @return array<string, mixed>
	 */
	public static function sub_array( array $data, string $key ): array {
		$value = $data[ $key ] ?? array();
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Coerce a raw scalar into an int, using the fallback for non-numeric input.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $fallback Value used when $value is not numeric.
	 * @return int
	 */
	public static function to_int( mixed $value, int $fallback ): int {
		return is_numeric( $value ) ? (int) $value : $fallback;
	}

	/**
	 * Coerce a raw scalar into a trimmed string, defaulting to empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function to_string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Coerce a raw value into a clean list of non-empty strings.
	 *
	 * @param mixed $value Raw value (expected to be an array of strings).
	 * @return string[]
	 */
	public static function string_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$strings = array();
		foreach ( $value as $item ) {
			if ( is_string( $item ) && '' !== trim( $item ) ) {
				$strings[] = trim( $item );
			}
		}

		return array_values( array_unique( $strings ) );
	}
}
