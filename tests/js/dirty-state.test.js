const { settingsPageHtml, loadAdminScript } = require('./fixture');

describe('dirty/saved status', () => {
	beforeEach(() => {
		document.body.innerHTML = settingsPageHtml();
		loadAdminScript();
	});

	function status() {
		return document.getElementById('cf-form-status');
	}

	test('starts clean (a freshly-rendered form matches the saved settings)', () => {
		expect(status().getAttribute('data-state')).toBe('saved');
		expect(status().querySelector('.cf-footer-status-text').textContent).toBe('All changes saved');
		expect(document.getElementById('cf-preview-stale').hidden).toBe(true);
	});

	test('an edit anywhere in the form marks it dirty, and the two states are never shown together', () => {
		var title = document.querySelector('input[name="seo_title"]');
		title.value = 'New title';
		title.dispatchEvent(new Event('input', { bubbles: true }));

		expect(status().getAttribute('data-state')).toBe('dirty');
		var text = status().querySelector('.cf-footer-status-text').textContent;
		expect(text).toBe('Draft changes');
		expect(text).not.toBe('All changes saved');
		expect(document.getElementById('cf-preview-stale').hidden).toBe(false);
	});

	test('selecting a different mode radio (change event) also marks the form dirty', () => {
		var newsRadio = document.querySelector('input[name="mode"][value="news"]');
		newsRadio.checked = true;
		newsRadio.dispatchEvent(new Event('change', { bubbles: true }));

		expect(status().getAttribute('data-state')).toBe('dirty');
	});

	test('a native form reset clears the dirty flag once the fields are back to their saved values', () => {
		var title = document.querySelector('input[name="seo_title"]');
		title.value = 'New title';
		title.dispatchEvent(new Event('input', { bubbles: true }));
		expect(status().getAttribute('data-state')).toBe('dirty');

		var form = document.getElementById('cf-settings-form');
		form.dispatchEvent(new Event('reset', { bubbles: true, cancelable: true }));

		return new Promise((resolve) => {
			setTimeout(() => {
				expect(status().getAttribute('data-state')).toBe('saved');
				expect(status().querySelector('.cf-footer-status-text').textContent).toBe('All changes saved');
				expect(document.getElementById('cf-preview-stale').hidden).toBe(true);
				resolve();
			}, 0);
		});
	});
});
