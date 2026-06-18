/**
 * CannyForge Archive — client-side search & filters (ticket 106).
 *
 * Progressive enhancement over the server-rendered archive: the full list is
 * present and crawlable without JavaScript; this script only narrows what is
 * visible. Filters combine with AND; the search box matches entry text.
 */
( function () {
	'use strict';

	/**
	 * Split a pipe-delimited data-attribute into a lowercase value list.
	 *
	 * @param {string} raw Raw attribute value.
	 * @return {string[]} Values.
	 */
	function values( raw ) {
		if ( ! raw ) {
			return [];
		}
		return raw.split( '|' ).map( function ( value ) {
			return value.trim().toLowerCase();
		} );
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

		function apply() {
			var term = search ? search.value.trim().toLowerCase() : '';

			items.forEach( function ( item ) {
				var visible = ! term || item.textContent.toLowerCase().indexOf( term ) !== -1;

				selects.forEach( function ( select ) {
					if ( visible ) {
						visible = matchesDimension( item, select.getAttribute( 'data-filter' ), select.value );
					}
				} );

				item.hidden = ! visible;
			} );
		}

		if ( search ) {
			search.addEventListener( 'input', apply );
		}
		selects.forEach( function ( select ) {
			select.addEventListener( 'change', apply );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.slice
			.call( document.querySelectorAll( '.cannyforge-archive' ) )
			.forEach( init );
	} );
}() );
