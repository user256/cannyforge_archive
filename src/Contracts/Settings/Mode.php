<?php
/**
 * Archive generation mode.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The two mutually exclusive archive modes from the brief.
 *
 * Blog mode draws from a curated top-URL list; News mode draws from everything
 * published within a recent window.
 */
enum Mode: string {
	case Blog   = 'blog';
	case News   = 'news';
	case Hybrid = 'hybrid';

	/**
	 * Resolve from a raw string, defaulting to Blog for anything unrecognised.
	 *
	 * @param mixed $value Raw stored value.
	 * @return self
	 */
	public static function from_value( mixed $value ): self {
		return self::tryFrom( is_string( $value ) ? $value : '' ) ?? self::Blog;
	}
}
