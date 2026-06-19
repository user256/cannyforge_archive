<?php
/**
 * Renders the client-side filter controls for the archive.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\Filters;

/**
 * Renders the search box and filter dropdowns the client-side JS (ticket 106)
 * enhances.
 *
 * Only the controls enabled in {@see Filters} are emitted; the select options
 * are derived from the entries themselves so the JS needs no extra data. The
 * controls are inert without JavaScript — the crawlable archive list rendered
 * by {@see ArchiveRenderer} remains the source of truth. All values are escaped.
 */
final class FilterControlsRenderer {
	/**
	 * Render the controls block for the enabled filters.
	 *
	 * @param ArchiveEntry[] $entries Entries the options are derived from.
	 * @param Filters        $filters Which controls are enabled.
	 * @return string HTML fragment (empty when no filter is enabled).
	 */
	public function render( array $entries, Filters $filters ): string {
		$controls = '';

		if ( $filters->search() ) {
			$controls .= $this->search_box();
		}

		if ( $filters->category() ) {
			$controls .= $this->select( 'category', __( 'All categories', 'cannyforge-archive' ), $this->categories( $entries ) );
		}

		if ( $filters->tag() ) {
			$controls .= $this->select( 'tag', __( 'All tags', 'cannyforge-archive' ), $this->tags( $entries ) );
		}

		if ( $filters->month_year() ) {
			$controls .= $this->select( 'month', __( 'All dates', 'cannyforge-archive' ), $this->months( $entries ) );
		}

		if ( $filters->author() ) {
			$controls .= $this->select( 'author', __( 'All authors', 'cannyforge-archive' ), $this->authors( $entries ) );
		}

		if ( '' === $controls ) {
			return '';
		}

		$controls .= $this->group_by();
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
	 * @param string   $filter  The filter key (data-filter attribute).
	 * @param string   $all     The "all" (no filter) option label.
	 * @param string[] $options The distinct option values.
	 * @return string
	 */
	private function select( string $filter, string $all, array $options ): string {
		$markup = sprintf(
			'<label class="cannyforge-archive-filters__field"><span class="cannyforge-archive-filters__label">%s</span><select class="cannyforge-archive-filters__select" data-filter="%s"><option value="">%s</option>',
			esc_html( $this->label_for( $filter ) ),
			esc_attr( $filter ),
			esc_html( $all )
		);

		foreach ( $options as $value ) {
			$markup .= sprintf( '<option value="%s">%1$s</option>', esc_html( $value ) );
		}

		return $markup . '</select></label>';
	}

	/**
	 * Render the display-grouping selector.
	 *
	 * @return string
	 */
	private function group_by(): string {
		return '<label class="cannyforge-archive-filters__field">'
			. '<span class="cannyforge-archive-filters__label">' . esc_html__( 'Group by', 'cannyforge-archive' ) . '</span>'
			. '<select class="cannyforge-archive-filters__select" data-display="group">'
			. '<option value="">' . esc_html__( 'Newest first', 'cannyforge-archive' ) . '</option>'
			. '<option value="category">' . esc_html__( 'Category', 'cannyforge-archive' ) . '</option>'
			. '<option value="tag">' . esc_html__( 'Tag / topic', 'cannyforge-archive' ) . '</option>'
			. '<option value="author">' . esc_html__( 'Author', 'cannyforge-archive' ) . '</option>'
			. '<option value="month">' . esc_html__( 'Month', 'cannyforge-archive' ) . '</option>'
			. '</select></label>';
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

	/**
	 * Distinct category labels across the entries, sorted.
	 *
	 * @param ArchiveEntry[] $entries Entries.
	 * @return string[]
	 */
	private function categories( array $entries ): array {
		return $this->distinct(
			$entries,
			static fn ( ArchiveEntry $entry ): array => $entry->categories()
		);
	}

	/**
	 * Distinct tag labels across the entries, sorted.
	 *
	 * @param ArchiveEntry[] $entries Entries.
	 * @return string[]
	 */
	private function tags( array $entries ): array {
		return $this->distinct(
			$entries,
			static fn ( ArchiveEntry $entry ): array => $entry->tags()
		);
	}

	/**
	 * Distinct author labels across the entries, sorted.
	 *
	 * @param ArchiveEntry[] $entries Entries.
	 * @return string[]
	 */
	private function authors( array $entries ): array {
		return $this->distinct(
			$entries,
			static fn ( ArchiveEntry $entry ): array => '' !== $entry->author() ? array( $entry->author() ) : array()
		);
	}

	/**
	 * Distinct publication months (Y-m) across the entries, newest first.
	 *
	 * @param ArchiveEntry[] $entries Entries.
	 * @return string[]
	 */
	private function months( array $entries ): array {
		$months = $this->distinct(
			$entries,
			static fn ( ArchiveEntry $entry ): array => '' !== $entry->published_month() ? array( $entry->published_month() ) : array()
		);
		rsort( $months );

		return $months;
	}

	/**
	 * Collect the distinct, sorted, non-empty values produced by an extractor.
	 *
	 * @param ArchiveEntry[] $entries   Entries.
	 * @param callable       $extractor Maps an entry to a string[] of values.
	 * @return string[]
	 */
	private function distinct( array $entries, callable $extractor ): array {
		$values = array();
		foreach ( $entries as $entry ) {
			foreach ( $extractor( $entry ) as $value ) {
				if ( '' !== $value ) {
					$values[ $value ] = true;
				}
			}
		}

		$distinct = array_keys( $values );
		sort( $distinct );

		return $distinct;
	}
}
