<?php
/**
 * Cached Search Console top-content IDs for page-render reads.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the last refreshed Search Console-derived post IDs.
 *
 * Page render reads only this cache; no Google API call happens during archive
 * rendering.
 */
final class SearchConsoleCacheStore {
	/**
	 * Option key holding the cached Search Console post IDs.
	 */
	public const OPTION_KEY = 'cannyforge_archive_google_search_console_cache';

	/**
	 * Get-option callable: fn(string $key, mixed $fallback): mixed.
	 *
	 * @var callable
	 */
	private $get_option;

	/**
	 * Set-option callable: fn(string $key, mixed $value): void.
	 *
	 * @var callable
	 */
	private $set_option;

	/**
	 * Construct the store.
	 *
	 * @param callable|null $get_option Get-option accessor.
	 * @param callable|null $set_option Set-option accessor.
	 */
	public function __construct( ?callable $get_option = null, ?callable $set_option = null ) {
		$this->get_option = $get_option ?? static function ( string $key, $fallback ) {
			return function_exists( 'get_option' ) ? get_option( $key, $fallback ) : $fallback;
		};
		$this->set_option = $set_option ?? static function ( string $key, $value ): void {
			if ( function_exists( 'update_option' ) ) {
				update_option( $key, $value, false );
			}
		};
	}

	/**
	 * The cached post IDs, cleaned and de-duplicated.
	 *
	 * @return int[]
	 */
	public function get_post_ids(): array {
		$stored = ( $this->get_option )( self::OPTION_KEY, array() );
		$data   = is_array( $stored ) ? $stored : array();
		$ids    = isset( $data['post_ids'] ) && is_array( $data['post_ids'] ) ? $data['post_ids'] : array();

		return $this->clean_ids( $ids );
	}

	/**
	 * Persist the cached post IDs with a refresh timestamp.
	 *
	 * @param int[]    $ids          Post IDs.
	 * @param int|null $refreshed_at Unix timestamp (defaults to now).
	 * @return void
	 */
	public function save_post_ids( array $ids, ?int $refreshed_at = null ): void {
		( $this->set_option )(
			self::OPTION_KEY,
			array(
				'post_ids'     => $this->clean_ids( $ids ),
				'refreshed_at' => $refreshed_at ?? time(),
			)
		);
	}

	/**
	 * Clear the cached Search Console IDs.
	 *
	 * @return void
	 */
	public function clear(): void {
		( $this->set_option )(
			self::OPTION_KEY,
			array(
				'post_ids'     => array(),
				'refreshed_at' => 0,
			)
		);
	}

	/**
	 * Clean an arbitrary list of raw IDs into unique positive integers.
	 *
	 * @param array<int, mixed> $ids Raw IDs.
	 * @return int[]
	 */
	private function clean_ids( array $ids ): array {
		$clean = array();

		foreach ( $ids as $id ) {
			if ( ! is_numeric( $id ) ) {
				continue;
			}

			$int = (int) $id;
			if ( $int > 0 && ! in_array( $int, $clean, true ) ) {
				$clean[] = $int;
			}
		}

		return $clean;
	}
}
