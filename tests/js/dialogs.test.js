const { settingsPageHtml, loadAdminScript } = require('./fixture');

describe('colour-editor dialog', () => {
	beforeEach(() => {
		document.body.innerHTML = settingsPageHtml();
		loadAdminScript();
	});

	function dialog() {
		return document.getElementById('cf-colors-modal');
	}

	test('the opener button shows the dialog and marks the body as modal-open', () => {
		expect(dialog().hasAttribute('open')).toBe(false);

		document.querySelector('[data-cf-dialog-open="colors"]').click();

		expect(dialog().hasAttribute('open')).toBe(true);
		expect(document.body.classList.contains('cannyforge-modal-open')).toBe(true);
	});

	test('either close control (the icon button or "Done") closes the dialog and clears the body class', () => {
		document.querySelector('[data-cf-dialog-open="colors"]').click();

		var closers = dialog().querySelectorAll('[data-cf-dialog-close]');
		expect(closers.length).toBe(2);

		closers[0].click();

		expect(dialog().hasAttribute('open')).toBe(false);
		expect(document.body.classList.contains('cannyforge-modal-open')).toBe(false);
	});

	test('clicking the dialog backdrop (outside its content box) closes it', () => {
		document.querySelector('[data-cf-dialog-open="colors"]').click();
		expect(dialog().hasAttribute('open')).toBe(true);

		// jsdom lays out everything at 0x0, so getBoundingClientRect() is an
		// empty rect at the origin; any click with positive coordinates is
		// therefore "outside" it, exactly like a real backdrop click.
		dialog().dispatchEvent(
			new MouseEvent('click', { bubbles: true, clientX: 50, clientY: 50 })
		);

		expect(dialog().hasAttribute('open')).toBe(false);
	});

	test('the close button has an accessible name (aria-label), not just a bare glyph', () => {
		var closeButtons = dialog().querySelectorAll('[data-cf-dialog-close]');
		var hasAccessibleName = Array.prototype.some.call(closeButtons, function (button) {
			return button.hasAttribute('aria-label') || '' !== button.textContent.trim();
		});

		expect(hasAccessibleName).toBe(true);
		expect(closeButtons[0].getAttribute('aria-label')).toBeTruthy();
	});
});

describe('Google wizard copy button', () => {
	beforeEach(() => {
		document.body.innerHTML =
			'<div class="cf-wizard-copy-controls">' +
				'<input type="text" id="cf-google-redirect-uri" value="https://example.test/wp-admin/admin-post.php?action=cb" readonly data-cf-select-on-focus>' +
				'<button type="button" data-cf-copy="#cf-google-redirect-uri">Copy</button>' +
			'</div>';
		Object.assign(navigator, {
			clipboard: { writeText: jest.fn() },
		});
		loadAdminScript();
	});

	test('copies the redirect URI input value and swaps the label to Copied', () => {
		var button = document.querySelector('[data-cf-copy]');
		button.click();

		expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
			'https://example.test/wp-admin/admin-post.php?action=cb'
		);
		expect(button.textContent).toBe('Copied');
	});

	test('focusing the read-only input selects its whole value', () => {
		var input = document.getElementById('cf-google-redirect-uri');
		input.select = jest.fn();

		input.dispatchEvent(new FocusEvent('focus'));

		expect(input.select).toHaveBeenCalled();
	});
});

describe('Search Console curation list', () => {
	test('adds selected page URLs to the curated list and marks the form dirty', () => {
		document.body.innerHTML =
			'<form id="cf-settings-form">' +
				'<textarea id="cf-blog-urls">https://example.test/existing/</textarea>' +
				'<section class="cf-search-console-curator">' +
					'<label><input type="checkbox" data-cf-search-console-page data-url="https://example.test/new-page/" checked> New page</label>' +
					'<button type="button" data-cf-add-search-console-pages data-target="#cf-blog-urls">Add selected</button>' +
					'<span data-cf-search-console-status></span>' +
				'</section>' +
				'<div id="cf-form-status" data-state="saved"><span class="cf-footer-status-text"></span></div>' +
			'</form>';
		loadAdminScript();

		document.querySelector('[data-cf-add-search-console-pages]').click();

		expect(document.getElementById('cf-blog-urls').value).toBe(
			'https://example.test/existing/\nhttps://example.test/new-page/'
		);
		expect(document.querySelector('[data-cf-search-console-status]').textContent).toContain('Added 1 page');
		expect(document.getElementById('cf-form-status').getAttribute('data-state')).toBe('dirty');
	});
});
