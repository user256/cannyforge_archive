/**
 * CannyForge Archive — whole-database search & filter navigation (ticket 301).
 *
 * The archive page server-renders the *promoted* set (newest / best), which is
 * crawlable with no JavaScript. This script adds the other half: as soon as the
 * user searches or applies a filter, it queries the whole content database via
 * an AJAX endpoint and shows paginated results — so genuinely old / non-promoted
 * posts are findable. With no active query, the promoted default view is shown.
 */
( function () {
	'use strict';

	var config = window.cannyforgeArchive || null;

	/**
	 * Read the active filter/search state from a form.
	 *
	 * @param {HTMLElement} form The filters form.
	 * @return {Object} State keyed by dimension.
	 */
	function readState( form ) {
		var search = form.querySelector( '[data-filter="search"]' );
		var state = {
			search: search ? search.value.trim() : '',
			category: '',
			tag: '',
			author: '',
			month: ''
		};

		Array.prototype.slice
			.call( form.querySelectorAll( 'select[data-filter]' ) )
			.forEach( function ( select ) {
				var key = select.getAttribute( 'data-filter' );
				if ( key in state ) {
					state[ key ] = select.value;
				}
			} );

		return state;
	}

	/**
	 * Whether any dimension constrains the query.
	 *
	 * @param {Object} state The filter state.
	 * @return {boolean} Active.
	 */
	function isActive( state ) {
		return !! ( state.search || state.category || state.tag || state.author || state.month );
	}

	/**
	 * Build the human-readable, screen-reader-announced summary of a
	 * search/filter result set, e.g. `3 results for "budget"` or
	 * `No results for "budget".`. When there is no search term (a select
	 * filter was used on its own) the query clause is omitted. Rendered into
	 * the `aria-live="polite"` status region (see ArchiveRenderer), so every
	 * change here — including the "no results" case — is announced to
	 * assistive tech with no extra wiring.
	 *
	 * @param {number} total The result count.
	 * @param {string} query The active search term, if any.
	 * @return {string}
	 */
	function resultSummaryText( total, query ) {
		var forQuery = query ? ' for "' + query + '"' : '';

		if ( 0 === total ) {
			return 'No results' + forQuery + '. Try a different search or clear your filters.';
		}

		return total + ' result' + ( 1 === total ? '' : 's' ) + forQuery;
	}

	/**
	 * Initialise the search/filter navigation for one archive nav.
	 *
	 * @param {HTMLElement} root The .cannyforge-archive element.
	 * @return {void}
	 */
	function init( root ) {
		var form = root.querySelector( '.cannyforge-archive-filters' );
		if ( ! form || ! config ) {
			return;
		}

		var promoted = root.querySelector( '[data-promoted-results]' );
		var results = root.querySelector( '[data-search-results]' );
		var resultsList = results ? results.querySelector( 'ul' ) : null;
		var summary = root.querySelector( '[data-results-summary]' );
		var empty = root.querySelector( '[data-empty-state]' );
		var pagination = root.querySelector( '[data-pagination]' );

		if ( ! promoted || ! results || ! resultsList || ! pagination ) {
			return;
		}

		var currentPage = 1;
		var requestToken = 0;

		function showPromoted() {
			promoted.hidden = false;
			results.hidden = true;
			pagination.hidden = true;
			if ( empty ) {
				empty.hidden = true;
			}
			if ( summary ) {
				summary.textContent = summary.getAttribute( 'data-default' ) || summary.textContent;
			}
		}

		function buildBody( state ) {
			var params = new URLSearchParams();
			params.set( 'action', config.action );
			params.set( 'nonce', config.nonce );
			params.set( 'search', state.search );
			params.set( 'category', state.category );
			params.set( 'tag', state.tag );
			params.set( 'author', state.author );
			params.set( 'month', state.month );
			params.set( 'page', String( currentPage ) );
			params.set( 'per_page', String( config.perPage || 20 ) );
			return params;
		}

		function renderPagination( data ) {
			pagination.innerHTML = '';
			if ( data.total_pages <= 1 ) {
				pagination.hidden = true;
				return;
			}

			function button( label, page, disabled, current, ariaLabel ) {
				var btn = document.createElement( 'button' );
				btn.type = 'button';
				// Shares the server-rendered pagination's class names (see
				// PaginationRenderer) so the AJAX-driven controls pick up the
				// same visible styling and focus indicator instead of
				// rendering unstyled.
				btn.className = 'cannyforge-pagination__page';
				if ( current ) {
					btn.className += ' is-current';
					btn.setAttribute( 'aria-current', 'page' );
				}
				btn.textContent = label;
				if ( ariaLabel ) {
					btn.setAttribute( 'aria-label', ariaLabel );
				}
				btn.disabled = !! disabled;
				if ( ! disabled && ! current ) {
					btn.addEventListener( 'click', function () {
						currentPage = page;
						run();
					} );
				}
				return btn;
			}

			pagination.appendChild(
				button( '‹ Prev', data.page - 1, ! data.has_prev, false, 'Previous page' )
			);

			var span = document.createElement( 'span' );
			span.className = 'cannyforge-pagination__page-status';
			span.textContent = 'Page ' + data.page + ' of ' + data.total_pages;
			pagination.appendChild( span );

			pagination.appendChild(
				button( 'Next ›', data.page + 1, ! data.has_next, false, 'Next page' )
			);

			pagination.hidden = false;
		}

		function showResults( data, state ) {
			promoted.hidden = true;
			results.hidden = false;
			resultsList.innerHTML = data.html;

			var noResults = data.total === 0;

			if ( empty ) {
				empty.hidden = ! noResults;
			}

			if ( summary ) {
				summary.textContent = resultSummaryText( data.total, state && state.search ? state.search : '' );
			}

			renderPagination( data );
		}

		function run() {
			var state = readState( form );

			if ( ! isActive( state ) ) {
				showPromoted();
				return;
			}

			var token = ++requestToken;

			fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: buildBody( state ).toString()
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					if ( token !== requestToken ) {
						return; // A newer request superseded this one.
					}
					if ( payload && payload.success && payload.data ) {
						showResults( payload.data, state );
					}
				} )
				.catch( function () {
					if ( token === requestToken ) {
						showPromoted();
					}
				} );
		}

		// Any filter/search change resets to page 1 and re-runs.
		function onChange() {
			currentPage = 1;
			run();
		}

		var search = form.querySelector( '[data-filter="search"]' );
		if ( search ) {
			search.addEventListener( 'input', debounce( onChange, 250 ) );
		}
		Array.prototype.slice
			.call( form.querySelectorAll( 'select[data-filter]' ) )
			.forEach( function ( select ) {
				select.addEventListener( 'change', onChange );
			} );
		form.addEventListener( 'reset', function () {
			window.setTimeout( function () {
				currentPage = 1;
				showPromoted();
			}, 0 );
		} );

		if ( summary && ! summary.getAttribute( 'data-default' ) ) {
			summary.setAttribute( 'data-default', summary.textContent );
		}
	}

	/**
	 * Debounce a function so rapid calls collapse to one trailing call.
	 *
	 * @param {Function} fn    The function.
	 * @param {number}   delay Delay in ms.
	 * @return {Function} Debounced wrapper.
	 */
	function debounce( fn, delay ) {
		var timer = null;
		return function () {
			var args = arguments;
			var self = this;
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				fn.apply( self, args );
			}, delay );
		};
	}

	if ( 'undefined' !== typeof document ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			Array.prototype.slice
				.call( document.querySelectorAll( '.cannyforge-archive' ) )
				.forEach( init );
		} );
	}

	// Exposed for unit tests only (no-op in the browser: `module` is undefined there).
	if ( 'undefined' !== typeof module && module.exports ) {
		module.exports = {
			readState: readState,
			isActive: isActive,
			resultSummaryText: resultSummaryText,
			debounce: debounce,
			init: init,
		};
	}
}() );
