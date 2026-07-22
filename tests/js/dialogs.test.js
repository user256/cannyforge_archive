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

describe('Google wizard credential save', () => {
	test('saves in place and keeps the wizard open', async () => {
		document.body.innerHTML = settingsPageHtml().replace(
			'</form>',
			'<dialog data-cf-google-wizard-dialog>' +
				'<div class="cannyforge-google-wizard__progress">' +
					'<strong data-cf-google-wizard-progress-title>Next: add credentials</strong>' +
					'<span data-cf-google-wizard-progress-message>Upload the JSON.</span>' +
				'</div>' +
				'<button type="submit" data-cf-google-save-details>Save credentials and continue</button>' +
				'<span data-cf-google-save-status hidden></span>' +
			'</dialog></form>'
		);
		window.fetch = jest.fn().mockResolvedValue({
			ok: true,
			text: () => Promise.resolve('<html><body><div class="notice-success">Settings saved.</div></body></html>'),
		});
		loadAdminScript();

		document.querySelector('[data-cf-google-save-details]').click();
		await new Promise((resolve) => setTimeout(resolve, 0));

		expect(window.fetch).toHaveBeenCalledTimes(1);
		expect(document.querySelector('[data-cf-google-wizard-dialog]').hasAttribute('open')).toBe(false);
		expect(document.querySelector('[data-cf-google-save-status]').textContent).toContain('Credentials saved');
		expect(document.querySelector('[data-cf-google-wizard-progress-title]').textContent).toContain('Next: connect');
	});
});
