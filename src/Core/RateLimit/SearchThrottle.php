<?php
/**
 * Per-IP request throttle for the public archive search endpoint.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\RateLimit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A lightweight, fixed-window per-IP request counter guarding {@see
 * \CannyForge\Archive\Frontend\ArchiveSearchEndpoint} (ticket 608).
 *
 * The endpoint is `nopriv` and needs only a nonce scraped once from any
 * archive page load to keep working, so nonce verification alone does not
 * bound how much query load a scripted client can generate. This throttle
 * adds a basic abuse ceiling: each IP gets a fixed number of requests per
 * fixed time window (a transient counter keyed by IP + window bucket), with
 * both figures filterable so a site under different traffic patterns can tune
 * them without a code change.
 *
 * Fixed-window rather than sliding-window: one transient read/write per
 * request, and precise enough for a basic abuse ceiling. The accepted
 * trade-off is that a client can burst up to roughly 2x the limit across a
 * window boundary (e.g. the limit again in the last second of one window and
 * again in the first second of the next) — not exact rate limiting, but
 * enough to blunt sustained scripted load.
 *
 * IP resolution intentionally has no proxy / `X-Forwarded-For` awareness: that
 * header is trivially spoofable unless a specific trusted-proxy configuration
 * validates it first, which varies per host and is out of scope for a
 * "lightweight" default. Sites behind a proxy should resolve the real client
 * IP into `REMOTE_ADDR` at the web-server/proxy layer, exactly as is already
 * required for WordPress's own IP-dependent behaviour to work correctly.
 */
final class SearchThrottle {
	/**
	 * Transient key prefix.
	 */
	private const PREFIX = 'cannyforge_archive_search_throttle_';

	/**
	 * Default requests allowed per window, before the {@see self::LIMIT_FILTER}
	 * filter is applied.
	 */
	private const DEFAULT_LIMIT = 30;

	/**
	 * Default window length in seconds, before the {@see self::WINDOW_FILTER}
	 * filter is applied.
	 */
	private const DEFAULT_WINDOW = MINUTE_IN_SECONDS;

	/**
	 * Filter controlling the per-window request limit.
	 */
	public const LIMIT_FILTER = 'cannyforge_archive_search_throttle_limit';

	/**
	 * Filter controlling the window length, in seconds.
	 */
	public const WINDOW_FILTER = 'cannyforge_archive_search_throttle_window';

	/**
	 * Record a request from the given IP and report whether it exceeds the
	 * current window's limit.
	 *
	 * An IP that cannot be determined (empty string) is never throttled —
	 * failing open for the rare case a real request truly carries none,
	 * rather than bucketing every such request together under one shared
	 * counter and false-positive-blocking unrelated visitors.
	 *
	 * @param string $ip The requesting client's IP address.
	 * @return bool True when this request should be rejected.
	 */
	public function is_exceeded( string $ip ): bool {
		$ip = trim( $ip );
		if ( '' === $ip ) {
			return false;
		}

		$key    = $this->key( $ip );
		$stored = get_transient( $key );
		$count  = ( is_numeric( $stored ) ? (int) $stored : 0 ) + 1;

		set_transient( $key, $count, $this->window() );

		return $count > $this->limit();
	}

	/**
	 * Clear the current window's counter for an IP.
	 *
	 * Used by tests to simulate window rollover without waiting for wall-clock
	 * time to pass; also available for a future admin "unblock this IP"
	 * action.
	 *
	 * @param string $ip The IP address to reset.
	 * @return void
	 */
	public function reset( string $ip ): void {
		delete_transient( $this->key( trim( $ip ) ) );
	}

	/**
	 * Build the transient key for an IP's current window bucket.
	 *
	 * @param string $ip The IP address.
	 * @return string
	 */
	private function key( string $ip ): string {
		$window = $this->window();
		$bucket = (int) floor( time() / $window );

		return self::PREFIX . md5( $ip ) . '_' . $bucket;
	}

	/**
	 * The current per-window request limit.
	 *
	 * @return int
	 */
	private function limit(): int {
		return max( 1, (int) apply_filters( self::LIMIT_FILTER, self::DEFAULT_LIMIT ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- LIMIT_FILTER is a fixed class constant, not a runtime-built name.
	}

	/**
	 * The current window length, in seconds.
	 *
	 * @return int
	 */
	private function window(): int {
		return max( 1, (int) apply_filters( self::WINDOW_FILTER, self::DEFAULT_WINDOW ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- WINDOW_FILTER is a fixed class constant, not a runtime-built name.
	}
}
