<?php
/**
 * Short-lived Search Console property list store.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the connected admin's property list out of the permanent options.
 */
final class SearchConsolePropertyStore {
	/**
	 * Cache lifetime in seconds.
	 */
	private const TTL = 600;

	/**
	 * Transient prefix.
	 */
	private const KEY_PREFIX = 'cannyforge_archive_sc_properties_';

	/**
	 * Get the current user's cached properties.
	 *
	 * @return array<int, array{site_url: string, permission: string}>
	 */
	public function get(): array {
		$stored = get_transient( $this->key() );
		return is_array( $stored ) ? $this->clean( $stored ) : array();
	}

	/**
	 * Cache a property list for the current admin.
	 *
	 * @param array<int, array{site_url: string, permission: string}> $properties Properties.
	 * @return void
	 */
	public function save( array $properties ): void {
		set_transient( $this->key(), $this->clean( $properties ), self::TTL );
	}

	/**
	 * Clear the current admin's property list.
	 *
	 * @return void
	 */
	public function clear(): void {
		delete_transient( $this->key() );
	}

	/**
	 * Build a user-specific transient key.
	 *
	 * @return string
	 */
	private function key(): string {
		return self::KEY_PREFIX . get_current_user_id();
	}

	/**
	 * Clean untrusted/transient data before rendering it.
	 *
	 * @param array<int, mixed> $properties Raw properties.
	 * @return array<int, array{site_url: string, permission: string}>
	 */
	private function clean( array $properties ): array {
		$clean = array();
		foreach ( $properties as $property ) {
			if ( ! is_array( $property ) || ! is_string( $property['site_url'] ?? null ) || '' === trim( $property['site_url'] ) ) {
				continue;
			}
			$clean[] = array(
				'site_url'   => trim( $property['site_url'] ),
				'permission' => is_string( $property['permission'] ?? null ) ? trim( $property['permission'] ) : '',
			);
		}
		return $clean;
	}
}
