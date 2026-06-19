/**
 * CannyForge Archive — client-side search & filters (ticket 106).
 *
 * Progressive enhancement over the server-rendered archive: the full list is
 * present and crawlable without JavaScript; this script only narrows what is
 * visible. Filters combine with AND; the search box matches entry text.
 */
( function () {
	'use strict';

	function splitValues( raw ) {
		if ( ! raw ) {
			return [];
		}
		return raw.split( '|' ).map( function ( value ) {
			return value.trim();
		} ).filter( Boolean );
	}

	function normalise( value ) {
		return value.trim().toLowerCase();
	}

	/**
	 * Split a pipe-delimited data-attribute into a lowercase value list.
	 *
	 * @param {string} raw Raw attribute value.
	 * @return {string[]} Values.
	 */
	function values( raw ) {
		return splitValues( raw ).map( normalise );
	}

	/**
	 * Whether an entry matches a single dimension's selected value.
	 *
	 * @param {HTMLElement} item     The entry element.
	 * @param {string}      filter   The filter key (category/tag/author/month).
	 * @param {string}      selected The selected value (empty = no constraint).
	 * @return {boolean} Match.
	 */
	function matchesDimension( item, filter, selected ) {
		if ( ! selected ) {
			return true;
		}

		var attribute = {
			category: 'categories',
			tag: 'tags',
			author: 'author',
			month: 'month'
		}[ filter ];

		return values( item.getAttribute( 'data-' + attribute ) ).indexOf( selected.toLowerCase() ) !== -1;
	}

	/**
	 * Initialise the filters for one archive nav.
	 *
	 * @param {HTMLElement} root The .cannyforge-archive element.
	 * @return {void}
	 */
	function init( root ) {
		var form = root.querySelector( '.cannyforge-archive-filters' );
		if ( ! form ) {
			return;
		}

		var items = Array.prototype.slice.call(
			root.querySelectorAll( '.cannyforge-archive__item' )
		);
		var search = form.querySelector( '[data-filter="search"]' );
		var selects = Array.prototype.slice.call(
			form.querySelectorAll( 'select[data-filter]' )
		);
		var group = form.querySelector( '[data-display="group"]' );
		var summary = root.querySelector( '[data-results-summary]' );
		var empty = root.querySelector( '[data-empty-state]' );
		var groupedResults = root.querySelector( '[data-grouped-results]' );
		var list = root.querySelector( '[data-archive-list]' );

		function groupLabel( filter, item ) {
			var attribute = {
				category: 'categories',
				tag: 'tags',
				author: 'author',
				month: 'month'
			}[ filter ];
			var labels = splitValues( item.getAttribute( 'data-' + attribute ) );

			if ( labels.length ) {
				return labels[ 0 ];
			}

			return {
				category: 'Uncategorised',
				tag: 'Untagged',
				author: 'Unknown author',
				month: 'Undated'
			}[ filter ] || 'Other';
		}

		function renderGrouped( visibleItems, mode ) {
			if ( ! groupedResults || ! list ) {
				return;
			}

			groupedResults.innerHTML = '';

			if ( ! mode ) {
				list.hidden = false;
				groupedResults.hidden = true;
				return;
			}

			var groups = {};
			var order = [];

			visibleItems.forEach( function ( item ) {
				var label = groupLabel( mode, item );
				if ( ! groups[ label ] ) {
					groups[ label ] = [];
					order.push( label );
				}
				groups[ label ].push( item );
			} );

			order.forEach( function ( label ) {
				var section = document.createElement( 'section' );
				section.className = 'cannyforge-archive-group';

				var heading = document.createElement( 'div' );
				heading.className = 'cannyforge-archive-group__header';
				heading.innerHTML = '<h3 class="cannyforge-archive-group__title"></h3><span class="cannyforge-archive-group__count"></span>';
				heading.querySelector( '.cannyforge-archive-group__title' ).textContent = label;
				heading.querySelector( '.cannyforge-archive-group__count' ).textContent = groups[ label ].length + ' item' + ( groups[ label ].length === 1 ? '' : 's' );

				var bucket = document.createElement( 'ul' );
				bucket.className = 'cannyforge-archive__list cannyforge-archive__list--grouped';

				groups[ label ].forEach( function ( item ) {
					var clone = item.cloneNode( true );
					clone.hidden = false;
					bucket.appendChild( clone );
				} );

				section.appendChild( heading );
				section.appendChild( bucket );
				groupedResults.appendChild( section );
			} );

			list.hidden = true;
			groupedResults.hidden = visibleItems.length === 0;
		}

		function updateSummary( visibleCount ) {
			if ( ! summary ) {
				return;
			}

			var text = visibleCount === items.length
				? 'Showing all ' + visibleCount + ' entries'
				: 'Showing ' + visibleCount + ' of ' + items.length + ' entries';

			if ( group && group.value ) {
				text += ', grouped by ' + group.options[ group.selectedIndex ].text.toLowerCase();
			}

			summary.textContent = text;
		}

		function apply() {
			var term = search ? search.value.trim().toLowerCase() : '';
			var visibleItems = [];

			items.forEach( function ( item ) {
				var visible = ! term || item.textContent.toLowerCase().indexOf( term ) !== -1;

				selects.forEach( function ( select ) {
					if ( visible ) {
						visible = matchesDimension( item, select.getAttribute( 'data-filter' ), select.value );
					}
				} );

				item.hidden = ! visible;
				if ( visible ) {
					visibleItems.push( item );
				}
			} );

			if ( empty ) {
				empty.hidden = visibleItems.length !== 0;
			}

			updateSummary( visibleItems.length );
			renderGrouped( visibleItems, group ? group.value : '' );
		}

		if ( search ) {
			search.addEventListener( 'input', apply );
		}
		selects.forEach( function ( select ) {
			select.addEventListener( 'change', apply );
		} );
		if ( group ) {
			group.addEventListener( 'change', apply );
		}
		form.addEventListener( 'reset', function () {
			window.setTimeout( apply, 0 );
		} );

		apply();
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.slice
			.call( document.querySelectorAll( '.cannyforge-archive' ) )
			.forEach( init );
	} );
}() );
