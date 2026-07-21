/**
 * Tests for the archive search/filter navigation (ticket 609 accessibility
 * pass): result-count announcements (including the "no results" state) and
 * the pagination controls the AJAX endpoint renders.
 */
const { resultSummaryText, isActive } = require('../../assets/js/archive-filters.js');

describe('resultSummaryText (announced via the aria-live="polite" status region)', () => {
	test('announces a plural result count with the search query', () => {
		expect(resultSummaryText(3, 'budget')).toBe('3 results for "budget"');
	});

	test('announces a singular result count', () => {
		expect(resultSummaryText(1, 'budget')).toBe('1 result for "budget"');
	});

	test('omits the query clause when no search term is active (select-only filter)', () => {
		expect(resultSummaryText(4, '')).toBe('4 results');
	});

	test('announces the "no results" state, including the query', () => {
		expect(resultSummaryText(0, 'zzzznotfound')).toBe(
			'No results for "zzzznotfound". Try a different search or clear your filters.'
		);
	});

	test('announces the "no results" state with no active search term', () => {
		expect(resultSummaryText(0, '')).toBe(
			'No results. Try a different search or clear your filters.'
		);
	});
});

describe('isActive', () => {
	test('false when no filter dimension is set', () => {
		expect(isActive({ search: '', category: '', tag: '', author: '', month: '' })).toBe(false);
	});

	test('true when any single dimension is set', () => {
		expect(isActive({ search: '', category: 'news', tag: '', author: '', month: '' })).toBe(true);
		expect(isActive({ search: 'hello', category: '', tag: '', author: '', month: '' })).toBe(true);
	});
});

describe('AJAX-driven pagination + result region wiring', () => {
	function archiveHtml() {
		return `
			<div class="cannyforge-archive">
				<form class="cannyforge-archive-filters" role="search">
					<input type="search" class="cannyforge-archive-filters__search" data-filter="search">
					<select data-filter="category">
						<option value="">All categories</option>
						<option value="news">News</option>
					</select>
				</form>
				<div class="cannyforge-archive__status" data-results-summary aria-live="polite">Showing all 5 entries</div>
				<p class="cannyforge-archive__empty" data-empty-state hidden>No entries match your current search and filters.</p>
				<div data-promoted-results><ul><li>Promoted item</li></ul></div>
				<div data-search-results hidden><ul></ul></div>
				<nav data-pagination hidden></nav>
			</div>
		`;
	}

	beforeEach(() => {
		jest.resetModules();
		document.body.innerHTML = archiveHtml();
		window.cannyforgeArchive = {
			action: 'cannyforge_archive_search',
			nonce: 'test-nonce',
			ajaxUrl: 'http://example.test/wp-admin/admin-ajax.php',
			perPage: 20,
		};
	});

	afterEach(() => {
		delete window.cannyforgeArchive;
		delete global.fetch;
	});

	function flushPromises() {
		return new Promise((resolve) => setTimeout(resolve, 0));
	}

	test('a zero-result search announces "No results" via the live region and shows the empty state', async () => {
		global.fetch = jest.fn().mockResolvedValue({
			json: () =>
				Promise.resolve({
					success: true,
					data: { html: '', total: 0, page: 1, total_pages: 0, has_prev: false, has_next: false },
				}),
		});

		// Re-require so this test's DOM gets its own DOMContentLoaded-bound init.
		// eslint-disable-next-line global-require
		const filters = require('../../assets/js/archive-filters.js');
		filters.init(document.querySelector('.cannyforge-archive'));

		const category = document.querySelector('select[data-filter="category"]');
		category.value = 'news';
		category.dispatchEvent(new Event('change'));

		await flushPromises();
		await flushPromises();

		const summary = document.querySelector('[data-results-summary]');
		const empty = document.querySelector('[data-empty-state]');

		expect(summary.textContent).toBe('No results. Try a different search or clear your filters.');
		expect(empty.hidden).toBe(false);
	});

	test('pagination buttons use the shared cannyforge-pagination__page class so they pick up the site theme + focus styling', async () => {
		global.fetch = jest.fn().mockResolvedValue({
			json: () =>
				Promise.resolve({
					success: true,
					data: {
						html: '<li>Result</li>',
						total: 45,
						page: 1,
						total_pages: 3,
						has_prev: false,
						has_next: true,
					},
				}),
		});

		// eslint-disable-next-line global-require
		const filters = require('../../assets/js/archive-filters.js');
		filters.init(document.querySelector('.cannyforge-archive'));

		const category = document.querySelector('select[data-filter="category"]');
		category.value = 'news';
		category.dispatchEvent(new Event('change'));

		await flushPromises();
		await flushPromises();

		const summary = document.querySelector('[data-results-summary]');
		expect(summary.textContent).toBe('45 results');

		const buttons = document.querySelectorAll('[data-pagination] .cannyforge-pagination__page');
		expect(buttons.length).toBe(2);
		expect(document.querySelectorAll('[data-pagination] .cannyforge-archive__page').length).toBe(0);
		expect(buttons[0].getAttribute('aria-label')).toBe('Previous page');
		expect(buttons[1].getAttribute('aria-label')).toBe('Next page');
	});
});
