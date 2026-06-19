<?php
/**
 * Renders archive entries as an HTML sitemap.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\LinkTypes;
use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Turns a list of {@see ArchiveEntry} into a crawlable HTML-sitemap fragment.
 *
 * Output is server-rendered and works with no JavaScript: a heading plus an
 * unordered list of links, each carrying the configured link-type fields and
 * filter metadata as data-attributes (consumed by the client-side filters in
 * ticket 106). All dynamic values are escaped.
 */
final class ArchiveRenderer {
	/**
	 * Renders the (optional) client-side filter controls.
	 *
	 * @var FilterControlsRenderer
	 */
	private FilterControlsRenderer $controls;

	/**
	 * Construct the renderer.
	 *
	 * @param FilterControlsRenderer|null $controls Filter-controls renderer.
	 */
	public function __construct( ?FilterControlsRenderer $controls = null ) {
		$this->controls = $controls ?? new FilterControlsRenderer();
	}

	/**
	 * Render the full archive: enabled filter controls then the crawlable list.
	 *
	 * @param ArchiveEntry[] $entries  Entries to render.
	 * @param Settings       $settings Current settings (drives link-type toggles).
	 * @return string HTML fragment.
	 */
	public function render( array $entries, Settings $settings ): string {
		$items = '';
		foreach ( $entries as $entry ) {
			$items .= $this->render_entry( $entry, $settings->link_types() );
		}

		$controls = $this->controls->render( $entries, $settings->filters() );
		$theme    = $settings->theme();

		return sprintf(
			'<nav class="cannyforge-archive %s" aria-label="%s">',
			esc_attr( 'is-layout-' . $theme->layout() ),
			esc_attr__( 'Archive', 'cannyforge-archive' )
		)
			. $this->intro()
			. $controls
			. '<div class="cannyforge-archive__status" data-results-summary aria-live="polite">'
			/* translators: %d is the number of archive entries currently shown. */
			. esc_html( sprintf( __( 'Showing all %d entries', 'cannyforge-archive' ), count( $entries ) ) )
			. '</div>'
			. '<p class="cannyforge-archive__empty" data-empty-state hidden>'
			. esc_html__( 'No entries match your current search and filters.', 'cannyforge-archive' )
			. '</p>'
			. '<div class="cannyforge-archive__results">'
			. '<div class="cannyforge-archive__groups" data-grouped-results hidden></div>'
			. '<ul class="cannyforge-archive__list" data-archive-list>' . $items . '</ul>'
			. '</div></nav>';
	}

	/**
	 * Render a single entry as a list item.
	 *
	 * @param ArchiveEntry $entry The entry.
	 * @param LinkTypes    $types Link-type toggles.
	 * @return string
	 */
	private function render_entry( ArchiveEntry $entry, LinkTypes $types ): string {
		$inner = '';

		if ( $types->featured_image() && '' !== $entry->featured_image_url() ) {
			$inner .= sprintf(
				'<img class="cannyforge-archive__image" src="%s" alt="" loading="lazy">',
				esc_url( $entry->featured_image_url() )
			);
		}

		$label  = $types->title() ? $entry->title() : $entry->url();
		$inner .= sprintf(
			'<a class="cannyforge-archive__link" href="%s">%s</a>',
			esc_url( $entry->url() ),
			esc_html( $label )
		);

		if ( $types->description() && '' !== $entry->description() ) {
			$inner .= sprintf(
				'<span class="cannyforge-archive__desc">%s</span>',
				esc_html( $entry->description() )
			);
		}

		$inner .= $this->meta( $entry );

		return sprintf( '<li class="cannyforge-archive__item"%s>%s</li>', $this->data_attributes( $entry ), $inner );
	}

	/**
	 * Render the explanatory intro and top-line stats.
	 *
	 * @return string
	 */
	private function intro(): string {
		return '<div class="cannyforge-archive__hero">'
			. '<h1 class="cannyforge-archive__title">' . esc_html__( 'Archive', 'cannyforge-archive' ) . '</h1>'
			. '</div>';
	}



	/**
	 * Render readable metadata chips beneath an entry.
	 *
	 * @param ArchiveEntry $entry The entry.
	 * @return string
	 */
	private function meta( ArchiveEntry $entry ): string {
		$parts = array();

		if ( array() !== $entry->categories() ) {
			$parts[] = '<span class="cannyforge-archive__meta-chip">'
				. esc_html( $entry->categories()[0] )
				. '</span>';
		}

		if ( '' !== $entry->author() ) {
			$parts[] = '<span class="cannyforge-archive__meta-chip">'
				. esc_html( $entry->author() )
				. '</span>';
		}

		$month = $this->human_month( $entry->published_date() );
		if ( '' !== $month ) {
			$parts[] = '<span class="cannyforge-archive__meta-chip">'
				. esc_html( $month )
				. '</span>';
		}

		if ( array() === $parts ) {
			return '';
		}

		return '<div class="cannyforge-archive__meta">' . implode( '', $parts ) . '</div>';
	}

	/**
	 * Format a stored Y-m-d date into a short month label.
	 *
	 * @param string $date Raw date.
	 * @return string
	 */
	private function human_month( string $date ): string {
		if ( '' === $date ) {
			return '';
		}

		$parsed = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date );

		return false === $parsed ? substr( $date, 0, 7 ) : $parsed->format( 'M Y' );
	}

	/**
	 * Build the filter data-attributes for an entry.
	 *
	 * @param ArchiveEntry $entry The entry.
	 * @return string A leading-space-prefixed attribute string.
	 */
	private function data_attributes( ArchiveEntry $entry ): string {
		$attributes = array(
			'data-categories' => implode( '|', $entry->categories() ),
			'data-tags'       => implode( '|', $entry->tags() ),
			'data-author'     => $entry->author(),
			'data-month'      => $entry->published_month(),
		);

		$out = '';
		foreach ( $attributes as $name => $value ) {
			if ( '' !== $value ) {
				$out .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
			}
		}

		return $out;
	}
}
