<?php
/**
 * Renders the shortened pagination markup.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Pagination;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\PaginationStyle;
use CannyForge\Archive\Contracts\Settings\Theme;

/**
 * Builds the "1 2 3 … View Archive →" replacement for the default paginated
 * tail.
 *
 * Pure and framework-free: given the pagination settings and archive URL it
 * returns escaped HTML.
 */
final class PaginationRenderer {
	/**
	 * Which page numbers should be shown for the given settings.
	 *
	 * Leading mode shows the first N pages. Leading+tail mode appends the
	 * penultimate and final pages when they exist, de-duplicated in order.
	 *
	 * @param int             $limit       Configured pagination limit (pages before the link).
	 * @param int             $total_pages Total pages available.
	 * @param PaginationStyle $style How the visible page numbers are selected.
	 * @return int[]
	 */
	public function visible_pages( int $limit, int $total_pages, PaginationStyle $style ): array {
		$visible = max( 0, min( $limit, $total_pages ) );
		$pages   = $visible > 0 ? range( 1, $visible ) : array();

		if ( PaginationStyle::LeadingWithTail === $style && $total_pages > 0 ) {
			$pages[] = max( 1, $total_pages - 1 );
			$pages[] = $total_pages;
		}

		$pages = array_values(
			array_unique(
				array_filter(
					$pages,
					static fn ( int $page ): bool => $page >= 1 && $page <= $total_pages
				)
			)
		);

		sort( $pages );

		return $pages;
	}

	/**
	 * Render the shortened pagination block.
	 *
	 * Emits the selected page links, marking the current page, then a "View
	 * Archive" link to $archive_url. Returns an empty string when there is
	 * nothing to show and no archive to link to.
	 *
	 * @param int                   $current       The current page number (1-based).
	 * @param int                   $total_pages   Total pages available.
	 * @param int                   $limit         Configured pagination limit.
	 * @param PaginationStyle       $style         How the visible page numbers are selected.
	 * @param string                $archive_url   Destination for the "View Archive" link.
	 * @param string                $archive_label Label for the archive link.
	 * @param callable(int): string $page_url     Maps a page number to its URL.
	 * @param Theme|null            $theme        Optional front-end theme settings.
	 * @return string
	 */
	public function render(
		int $current,
		int $total_pages,
		int $limit,
		PaginationStyle $style,
		string $archive_url,
		string $archive_label,
		callable $page_url,
		?Theme $theme = null
	): string {
		$links = '';
		foreach ( $this->visible_pages( $limit, $total_pages, $style ) as $page ) {
			$links .= $this->page_link( $page, $current, (string) $page_url( $page ) );
		}

		$archive = '' !== $archive_url ? $this->archive_link( $archive_url, $archive_label ) : '';

		if ( '' === $links && '' === $archive ) {
			return '';
		}

		return sprintf(
			'<nav class="cannyforge-pagination"%s>%s%s</nav>',
			$this->theme_style( $theme ),
			$links,
			$archive
		);
	}

	/**
	 * Render a single page link (or a marked span for the current page).
	 *
	 * @param int    $page    The page number.
	 * @param int    $current The current page.
	 * @param string $url     The page URL.
	 * @return string
	 */
	private function page_link( int $page, int $current, string $url ): string {
		if ( $page === $current ) {
			return sprintf(
				'<span class="cannyforge-pagination__page is-current" aria-current="page">%d</span>',
				$page
			);
		}

		return sprintf(
			'<a class="cannyforge-pagination__page" href="%s">%d</a>',
			esc_url( $url ),
			$page
		);
	}

	/**
	 * Render the "View Archive" link.
	 *
	 * @param string $url   The archive URL.
	 * @param string $label The link label.
	 * @return string
	 */
	private function archive_link( string $url, string $label ): string {
		return sprintf(
			'<a class="cannyforge-pagination__archive" href="%s">%s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/**
	 * Render inline CSS variables carrying the configured theme.
	 *
	 * @param Theme|null $theme The theme settings, when configured.
	 * @return string A leading-space-prefixed style attribute, or empty.
	 */
	private function theme_style( ?Theme $theme ): string {
		if ( ! $theme instanceof Theme ) {
			return '';
		}

		$variables = sprintf(
			'--cf-accent:%s;--cf-surface:%s;--cf-text:%s;--cf-border:%s;',
			$theme->accent_color(),
			$theme->surface_color(),
			$theme->text_color(),
			$theme->border_color()
		);

		return sprintf( ' style="%s"', esc_attr( $variables ) );
	}
}
