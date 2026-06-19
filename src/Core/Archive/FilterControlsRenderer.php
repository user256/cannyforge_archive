<?php
/**
 * Renders the client-side filter controls for the archive.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Settings\Filters;

/**
 * Renders the search box and filter dropdowns the client-side JS enhances.
 *
 * Only the controls enabled in {@see Filters} are emitted. The select options
 * are supplied as whole-database `[value, label]` lists (ticket 301) so a filter
 * surfaces *all* site content, not just the promoted entries currently rendered.
 * The controls submit to the search endpoint; the crawlable promoted list
 * rendered by {@see ArchiveRenderer} remains the no-JS default. All values are
 * escaped.
 *
 * @phpstan-type OptionList array<int, array{value: string, label: string}>
 */
final class FilterControlsRenderer {
	/**
	 * Render the controls block for the enabled filters.
	 *
	 * @param Filters                                                        $filters Which controls are enabled.
	 * @param array<string, array<int, array{value: string, label: string}>> $options Whole-database option lists keyed by dimension (category/tag/author/month).
	 * @return string HTML fragment (empty when no filter is enabled).
	 */
	public function render( Filters $filters, array $options ): string {
		$controls = '';

		if ( $filters->search() ) {
			$controls .= $this->search_box();
		}

		if ( $filters->category() ) {
			$controls .= $this->select( 'category', __( 'All categories', 'cannyforge-archive' ), $options['category'] ?? array() );
		}

		if ( $filters->tag() ) {
			$controls .= $this->select( 'tag', __( 'All tags', 'cannyforge-archive' ), $options['tag'] ?? array() );
		}

		if ( $filters->month_year() ) {
			$controls .= $this->select( 'month', __( 'All dates', 'cannyforge-archive' ), $options['month'] ?? array() );
		}

		if ( $filters->author() ) {
			$controls .= $this->select( 'author', __( 'All authors', 'cannyforge-archive' ), $options['author'] ?? array() );
		}

		if ( '' === $controls ) {
			return '';
		}

		$controls .= $this->reset_button();

		return '<form class="cannyforge-archive-filters" role="search">'
			. '<div class="cannyforge-archive-filters__intro" style="margin-bottom: 1rem; font-weight: 600;">'
			. esc_html__( 'Find an article', 'cannyforge-archive' )
			. '</div>'
			. '<div class="cannyforge-archive-filters__grid">'
			. $controls
			. '</div></form>';
	}

	/**
	 * Render the search input.
	 *
	 * @return string
	 */
	private function search_box(): string {
		return sprintf(
			'<label class="cannyforge-archive-filters__field"><span class="cannyforge-archive-filters__label">%s</span><input type="search" class="cannyforge-archive-filters__search" data-filter="search" placeholder="%s" aria-label="%s"></label>',
			esc_html__( 'Search', 'cannyforge-archive' ),
			esc_attr__( 'Search the archive', 'cannyforge-archive' ),
			esc_attr__( 'Search the archive', 'cannyforge-archive' )
		);
	}

	/**
	 * Render a labelled select for one filter dimension.
	 *
	 * @param string                                          $filter  The filter key (data-filter attribute).
	 * @param string                                          $all     The "all" (no filter) option label.
	 * @param array<int, array{value: string, label: string}> $options Whole-database value/label pairs.
	 * @return string
	 */
	private function select( string $filter, string $all, array $options ): string {
		$markup = sprintf(
			'<label class="cannyforge-archive-filters__field"><span class="cannyforge-archive-filters__label">%s</span><select class="cannyforge-archive-filters__select" data-filter="%s"><option value="">%s</option>',
			esc_html( $this->label_for( $filter ) ),
			esc_attr( $filter ),
			esc_html( $all )
		);

		foreach ( $options as $option ) {
			$markup .= sprintf(
				'<option value="%s">%s</option>',
				esc_attr( $option['value'] ),
				esc_html( $option['label'] )
			);
		}

		return $markup . '</select></label>';
	}

	/**
	 * Render the reset button for the client-side controls.
	 *
	 * @return string
	 */
	private function reset_button(): string {
		return '<div class="cannyforge-archive-filters__actions"><button type="reset" class="cannyforge-archive-filters__reset">'
			. esc_html__( 'Reset', 'cannyforge-archive' )
			. '</button></div>';
	}

	/**
	 * Human labels for the filter dimensions.
	 *
	 * @param string $filter Filter key.
	 * @return string
	 */
	private function label_for( string $filter ): string {
		return match ( $filter ) {
			'category' => __( 'Category', 'cannyforge-archive' ),
			'tag'      => __( 'Tag', 'cannyforge-archive' ),
			'month'    => __( 'Published', 'cannyforge-archive' ),
			'author'   => __( 'Author', 'cannyforge-archive' ),
			default    => __( 'Filter', 'cannyforge-archive' ),
		};
	}
}
