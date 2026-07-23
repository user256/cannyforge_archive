<?php
/**
 * Cached GA4 top-content IDs for page-render reads.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the last refreshed GA4-derived post IDs.
 *
 * Page render reads only this cache; no GA4 API call happens during archive
 * rendering. Mirrors {@see SearchConsoleCacheStore} under a separate option key
 * so the two Google signals never overwrite each other.
 */
final class Ga4CacheStore {
	/**
	 * Option key holding the cached GA4 post IDs.
	 */
	public const OPTION_KEY = 'cannyforge_archive_google_ga4_cache';

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
	 * The raw page paths returned by the last GA4 refresh.
	 *
	 * These are retained for diagnostics when a local/staging install cannot
	 * resolve production paths to local published posts.
	 *
	 * @return string[]
	 */
	public function get_source_urls(): array {
		$stored = ( $this->get_option )( self::OPTION_KEY, array() );
		$data   = is_array( $stored ) ? $stored : array();
		$urls   = isset( $data['source_urls'] ) && is_array( $data['source_urls'] ) ? $data['source_urls'] : array();

		return $this->clean_urls( $urls );
	}

	/**
	 * Persist the cached post IDs with a refresh timestamp.
	 *
	 * @param int[]    $ids          Post IDs.
	 * @param int|null $refreshed_at Unix timestamp (defaults to now).
	 * @return void
	 */
	public function save_post_ids( array $ids, ?int $refreshed_at = null ): void {
		$this->save_results( $ids, array(), $refreshed_at );
	}

	/**
	 * Persist local IDs and the raw page paths returned by GA4.
	 *
	 * @param int[]    $ids          Matched local post IDs.
	 * @param string[] $source_urls  Raw page paths returned by Google.
	 * @param int|null $refreshed_at Unix timestamp (defaults to now).
	 * @return void
	 */
	public function save_results( array $ids, array $source_urls, ?int $refreshed_at = null ): void {
		( $this->set_option )(
			self::OPTION_KEY,
			array(
				'post_ids'     => $this->clean_ids( $ids ),
				'source_urls'  => $this->clean_urls( $source_urls ),
				'refreshed_at' => $refreshed_at ?? time(),
			)
		);
	}

	/**
	 * Clear the cached GA4 IDs.
	 *
	 * @return void
	 */
	public function clear(): void {
		( $this->set_option )(
			self::OPTION_KEY,
			array(
				'post_ids'     => array(),
				'source_urls'  => array(),
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

	/**
	 * Clean raw source URLs into unique non-empty strings.
	 *
	 * @param array<int, mixed> $urls Raw page paths or URLs.
	 * @return string[]
	 */
	private function clean_urls( array $urls ): array {
		$clean = array();

		foreach ( $urls as $url ) {
			if ( ! is_string( $url ) ) {
				continue;
			}

			$url = trim( $url );
			if ( '' !== $url && ! in_array( $url, $clean, true ) ) {
				$clean[] = $url;
			}
		}

		return $clean;
	}
}
