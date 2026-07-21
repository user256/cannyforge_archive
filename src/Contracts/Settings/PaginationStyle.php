<?php
/**
 * Pagination display style.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls how the shortened pagination chooses which page numbers to show.
 */
enum PaginationStyle: string {
	case Leading         = 'leading';
	case LeadingWithTail = 'leading_tail';

	/**
	 * Resolve from a raw string, defaulting to the original leading-pages behaviour.
	 *
	 * @param mixed $value Raw stored value.
	 * @return self
	 */
	public static function from_value( mixed $value ): self {
		return self::tryFrom( is_string( $value ) ? $value : '' ) ?? self::Leading;
	}
}
