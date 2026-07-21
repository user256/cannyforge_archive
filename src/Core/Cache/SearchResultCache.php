<?php
/**
 * Fragment cache for whole-database archive search/filter responses.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ContentQuery;

/**
 * Caches the JSON-serialisable response payload of {@see
 * \CannyForge\Archive\Frontend\ArchiveSearchEndpoint} using the WordPress
 * Transients API (ticket 608): the whole-database search endpoint previously
 * ran an uncached `WP_Query` on every request, unlike the HTML-sitemap
 * fragment ({@see ArchiveCache}, ticket 206).
 *
 * Unlike {@see ArchiveCache} — one fixed key per archive {@see
 * \CannyForge\Archive\Contracts\Settings\Mode} — a search response's cache key
 * depends on the normalised request (search term, filters, pagination), which
 * is unbounded in cardinality: a hostile or merely curious visitor can mint an
 * effectively unlimited number of distinct queries. WordPress's Transients API
 * has no wildcard/prefix delete, so enumerating and deleting every key ever
 * written — the {@see ArchiveCache} approach, viable there only because it
 * enumerates a fixed, small {@see \CannyForge\Archive\Contracts\Settings\Mode}
 * set — is not viable here.
 *
 * Instead, {@see self::clear()} advances a generation counter (a single, small
 * option): every cache key embeds the current generation, so bumping it
 * instantly and cheaply orphans every previously-cached response without ever
 * needing to know their keys. Orphaned entries fall out of the transients
 * table on their own TTL rather than being actively deleted — an accepted
 * trade-off for O(1) invalidation instead of an unbounded enumeration.
 */
final class SearchResultCache {
	/**
	 * Transient key prefix.
	 */
	private const PREFIX = 'cannyforge_archive_search_';

	/**
	 * The option holding the current cache generation.
	 */
	private const GENERATION_OPTION = 'cannyforge_archive_search_cache_generation';

	/**
	 * Cache TTL. Deliberately shorter than {@see ArchiveCache}'s: search draws
	 * far more distinct keys (every unique query combination is its own
	 * transient row), so a shorter TTL bounds how long one-off / bot-driven
	 * query variations linger in the transients table between generation
	 * bumps, rather than accumulating indefinitely.
	 */
	private const TTL = HOUR_IN_SECONDS;

	/**
	 * Retrieve a cached response payload when present.
	 *
	 * @param ContentQuery $query The request.
	 * @return array<string, mixed>|false Cached payload, or false on miss.
	 */
	public function get( ContentQuery $query ): array|false {
		$cached = get_transient( $this->key( $query ) );

		return is_array( $cached ) ? $cached : false;
	}

	/**
	 * Store a response payload in the transient cache.
	 *
	 * @param ContentQuery         $query   The request.
	 * @param array<string, mixed> $payload The response payload to cache.
	 * @return void
	 */
	public function set( ContentQuery $query, array $payload ): void {
		set_transient( $this->key( $query ), $payload, self::TTL );
	}

	/**
	 * Invalidate every cached search response by advancing the generation
	 * counter, so every previously-issued cache key stops matching.
	 *
	 * @return void
	 */
	public function clear(): void {
		update_option( self::GENERATION_OPTION, $this->generation() + 1, false );
	}

	/**
	 * Build the cache key from the current generation plus the normalised
	 * query parameters ({@see ContentQuery} has already clamped/trimmed each
	 * of these, so equivalent requests always normalise to the same key).
	 *
	 * @param ContentQuery $query The request.
	 * @return string
	 */
	private function key( ContentQuery $query ): string {
		$normalised = implode(
			'|',
			array(
				$query->search(),
				$query->category(),
				$query->tag(),
				$query->author(),
				$query->month(),
				(string) $query->page(),
				(string) $query->per_page(),
			)
		);

		return self::PREFIX . $this->generation() . '_' . md5( $normalised );
	}

	/**
	 * The current cache generation.
	 *
	 * WordPress may return a stored integer back as a numeric string (options
	 * are persisted as text), so this checks `is_numeric()` rather than
	 * casting the raw (potentially non-numeric) option value directly.
	 *
	 * @return int
	 */
	private function generation(): int {
		$stored = get_option( self::GENERATION_OPTION, 1 );

		return is_numeric( $stored ) ? (int) $stored : 1;
	}
}
