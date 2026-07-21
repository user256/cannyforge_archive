const { settingsPageHtml, loadAdminScript } = require('./fixture');

describe('mode radios stay real, focusable form controls', () => {
	beforeEach(() => {
		document.body.innerHTML = settingsPageHtml();
		loadAdminScript();
	});

	test('the radios are not display:none and are not disabled/hidden from the tab order', () => {
		var radios = Array.prototype.slice.call(document.querySelectorAll('input[name="mode"]'));

		expect(radios).toHaveLength(3);
		radios.forEach((radio) => {
			expect(radio.style.display).not.toBe('none');
			expect(radio.hasAttribute('hidden')).toBe(false);
			expect(radio.disabled).toBe(false);
			expect(radio.type).toBe('radio');
			// The clip/absolute-position technique lives in a CSS class, not inline display:none.
			expect(radio.className).toContain('cf-visually-hidden');
		});
	});

	test('the radio group keeps native single-selection behaviour: selecting one clears the others', () => {
		var news = document.querySelector('input[name="mode"][value="news"]');
		var blog = document.querySelector('input[name="mode"][value="blog"]');
		var hybrid = document.querySelector('input[name="mode"][value="hybrid"]');

		expect(blog.checked).toBe(true);

		news.checked = true;
		news.dispatchEvent(new Event('change', { bubbles: true }));

		expect(news.checked).toBe(true);
		expect(blog.checked).toBe(false);
		expect(hybrid.checked).toBe(false);
	});

	test('a native form reset restores the mode that was checked at page load', () => {
		var news = document.querySelector('input[name="mode"][value="news"]');
		var blog = document.querySelector('input[name="mode"][value="blog"]');

		news.checked = true;
		news.dispatchEvent(new Event('change', { bubbles: true }));
		expect(blog.checked).toBe(false);

		var form = document.getElementById('cf-settings-form');
		form.reset();

		expect(blog.checked).toBe(true);
		expect(news.checked).toBe(false);
	});
});
