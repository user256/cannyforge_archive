<?php
/**
 * Builds the archive page's SEO head tags.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Seo;

use CannyForge\Archive\Contracts\Settings\Seo;

/**
 * Turns the {@see Seo} settings into the archive page's `<head>` markup.
 *
 * Pure and framework-free so the directive combinations and canonical fallback
 * are unit-testable without WordPress. Emits a robots directive always, a title
 * and description only when set, and a canonical that falls back to the
 * archive's own URL when no override is configured. All values are escaped.
 */
final class HeadTagBuilder {
	/**
	 * Build the head-tag fragment for the archive page.
	 *
	 * @param Seo    $seo           The SEO settings.
	 * @param string $fallback_url  The archive's own URL (canonical fallback).
	 * @param bool   $include_title Whether to emit a `<title>` tag (false when the
	 *                              theme owns the document title via a filter).
	 * @return string
	 */
	public function build( Seo $seo, string $fallback_url, bool $include_title = true ): string {
		$tags = $this->robots_tag( $seo->robots() );

		if ( $include_title && '' !== $seo->title() ) {
			$tags .= sprintf( '<title>%s</title>', esc_html( $seo->title() ) );
		}

		if ( '' !== $seo->meta_description() ) {
			$tags .= sprintf(
				'<meta name="description" content="%s">',
				esc_attr( $seo->meta_description() )
			);
		}

		$canonical = '' !== $seo->canonical() ? $seo->canonical() : $fallback_url;
		if ( '' !== $canonical ) {
			$tags .= sprintf( '<link rel="canonical" href="%s">', esc_url( $canonical ) );
		}

		return $tags;
	}

	/**
	 * Render the robots meta tag.
	 *
	 * @param string $robots The robots directive string.
	 * @return string
	 */
	private function robots_tag( string $robots ): string {
		return sprintf( '<meta name="robots" content="%s">', esc_attr( $robots ) );
	}
}
