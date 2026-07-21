<?php
/**
 * Builds CSS variables for the archive theme settings.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Theme;

/**
 * Maps the theme settings to the CSS variables consumed by the front-end stylesheet.
 */
final class ThemeCssBuilder {
	/**
	 * Build the CSS-variable block for both the archive and pagination classes.
	 *
	 * @param Theme $theme Theme settings.
	 * @return string
	 */
	public function build( Theme $theme ): string {
		$css = sprintf(
			'.cannyforge-archive,.cannyforge-pagination{--cf-accent:%1$s;--cf-surface:%2$s;--cf-text:%3$s;--cf-border:%4$s;}',
			$theme->accent_color(),
			$theme->surface_color(),
			$theme->text_color(),
			$theme->border_color()
		);

		return apply_filters( 'cannyforge_archive_theme_css', $css, $theme );
	}
}
