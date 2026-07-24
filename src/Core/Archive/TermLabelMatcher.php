<?php
/**
 * Shared category/tag label matching for content selection.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compares term labels the same way page-one selection and full-archive
 * continuation queries must agree (ticket 730).
 *
 * Matching is case-insensitive after trim. Punctuation and spacing are
 * significant, matching WordPress `tax_query` `field => 'name'` behaviour
 * under a typical case-insensitive collation: `"News & Events"` does not
 * equal `"news-events"`, but `"News"` equals `"news"`.
 */
final class TermLabelMatcher {
	/**
	 * Whether two label lists share at least one value.
	 *
	 * @param string[] $haystack Labels on an entry (or query result).
	 * @param string[] $needles  Configured include/exclude labels.
	 * @return bool
	 */
	public static function intersects( array $haystack, array $needles ): bool {
		if ( array() === $haystack || array() === $needles ) {
			return false;
		}

		$needle_keys = array();
		foreach ( $needles as $needle ) {
			$key = self::normalize( $needle );
			if ( '' !== $key ) {
				$needle_keys[ $key ] = true;
			}
		}

		if ( array() === $needle_keys ) {
			return false;
		}

		foreach ( $haystack as $label ) {
			$key = self::normalize( $label );
			if ( '' !== $key && isset( $needle_keys[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Canonical form for comparison: trimmed, lowercased, punctuation kept.
	 *
	 * @param string $term Raw term name or stored selection label.
	 * @return string
	 */
	public static function normalize( string $term ): string {
		return strtolower( trim( $term ) );
	}
}
