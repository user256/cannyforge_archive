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

		return '<nav class="cannyforge-archive" aria-label="' . esc_attr__( 'Archive', 'cannyforge-archive' ) . '">'
			. $controls
			. '<ul class="cannyforge-archive__list">' . $items . '</ul></nav>';
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

		return sprintf( '<li class="cannyforge-archive__item"%s>%s</li>', $this->data_attributes( $entry ), $inner );
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
