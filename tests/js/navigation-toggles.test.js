const { settingsPageHtml, loadAdminScript } = require('./fixture');

describe('nav/accordion sync and panel toggles', () => {
	beforeEach(() => {
		document.body.innerHTML = settingsPageHtml();
		loadAdminScript();
		// jsdom has no smooth-scroll implementation; stub it so the click
		// handlers (which call scrollIntoView) don't throw.
		Element.prototype.scrollIntoView = jest.fn();
	});

	test('clicking a nav link opens the matching accordion and marks the link active', () => {
		var displayLink = document.querySelector('.cf-app-nav a[href="#tab-display"]');
		var accordion = document.getElementById('accordion-display');

		expect(accordion.open).toBeFalsy();

		displayLink.click();

		expect(accordion.open).toBe(true);
		expect(displayLink.parentElement.classList.contains('active')).toBe(true);
		expect(document.querySelector('.cf-app-nav a[href="#tab-content"]').parentElement.classList.contains('active')).toBe(false);
	});

	test('the nav-collapse toggle flips both the container class and its own aria-expanded', () => {
		var toggle = document.getElementById('cf-nav-toggle');
		var container = document.querySelector('.cf-app-container');

		expect(toggle.getAttribute('aria-expanded')).toBe('true');

		toggle.click();

		expect(container.classList.contains('nav-collapsed')).toBe(true);
		expect(toggle.getAttribute('aria-expanded')).toBe('false');
	});

	test('the live-preview toggle flips both the container class and its own aria-expanded', () => {
		var toggle = document.getElementById('cf-preview-toggle');
		var container = document.querySelector('.cf-app-container');

		expect(container.classList.contains('preview-hidden')).toBe(true);
		expect(toggle.getAttribute('aria-expanded')).toBe('false');

		toggle.click();

		expect(container.classList.contains('preview-hidden')).toBe(false);
		expect(toggle.getAttribute('aria-expanded')).toBe('true');
	});
});
