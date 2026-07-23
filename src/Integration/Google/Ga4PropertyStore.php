<?php
/**
 * Short-lived GA4 property list store.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the connected admin's GA4 property list out of permanent options.
 */
final class Ga4PropertyStore {
	/** Cache lifetime in seconds. */
	private const TTL = 600;

	/** Transient key prefix. */
	private const KEY_PREFIX = 'cannyforge_archive_ga4_properties_';

	/**
	 * Get the current user's cached properties.
	 *
	 * @return array<int, array{property_id: string, display_name: string, account_name: string}>
	 */
	public function get(): array {
		$stored = get_transient( $this->key() );
		return is_array( $stored ) ? $this->clean( $stored ) : array();
	}

	/**
	 * Cache a property list.
	 *
	 * @param array<int, array{property_id: string, display_name: string, account_name: string}> $properties Properties.
	 * @return void
	 */
	public function save( array $properties ): void {
		set_transient( $this->key(), $this->clean( $properties ), self::TTL );
	}

	/** Clear the current user's property list. */
	public function clear(): void {
		delete_transient( $this->key() );
	}

	/**
	 * Return the user-specific transient key.
	 *
	 * @return string User-specific transient key.
	 */
	private function key(): string {
		return self::KEY_PREFIX . get_current_user_id();
	}

	/**
	 * Clean cached data before rendering it.
	 *
	 * @param array<int, mixed> $properties Raw properties.
	 * @return array<int, array{property_id: string, display_name: string, account_name: string}>
	 */
	private function clean( array $properties ): array {
		$clean = array();
		foreach ( $properties as $property ) {
			if ( ! is_array( $property ) || ! is_string( $property['property_id'] ?? null ) || ! preg_match( '/^\d+$/', $property['property_id'] ) ) {
				continue;
			}
			$clean[] = array(
				'property_id'  => $property['property_id'],
				'display_name' => is_string( $property['display_name'] ?? null ) ? trim( $property['display_name'] ) : '',
				'account_name' => is_string( $property['account_name'] ?? null ) ? trim( $property['account_name'] ) : '',
			);
		}
		return $clean;
	}
}
