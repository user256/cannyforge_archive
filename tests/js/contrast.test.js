/**
 * Tests for the WCAG AA contrast-ratio maths and the "Edit Colours" dialog's
 * live contrast warning (ticket 609).
 */
const { hexToRgb, relativeLuminance, contrastRatio } = require('../../assets/js/admin.js');
const { settingsPageHtml, loadAdminScript } = require('./fixture');

describe('hexToRgb', () => {
	test('parses a 6-digit hex colour', () => {
		expect(hexToRgb('#1b143f')).toEqual({ r: 27, g: 20, b: 63 });
	});

	test('parses a 3-digit shorthand hex colour', () => {
		expect(hexToRgb('#fff')).toEqual({ r: 255, g: 255, b: 255 });
	});

	test('is case-insensitive and tolerates missing leading #', () => {
		expect(hexToRgb('FFFFFF')).toEqual({ r: 255, g: 255, b: 255 });
	});

	test('returns null for an unparseable value', () => {
		expect(hexToRgb('not-a-colour')).toBeNull();
		expect(hexToRgb('')).toBeNull();
	});
});

describe('relativeLuminance', () => {
	test('white is fully luminant, black is not', () => {
		expect(relativeLuminance({ r: 255, g: 255, b: 255 })).toBeCloseTo(1, 5);
		expect(relativeLuminance({ r: 0, g: 0, b: 0 })).toBeCloseTo(0, 5);
	});
});

describe('contrastRatio', () => {
	test('black on white is the maximum 21:1', () => {
		expect(contrastRatio('#000000', '#ffffff')).toBeCloseTo(21, 0);
	});

	test('identical colours have no contrast (1:1)', () => {
		expect(contrastRatio('#6d4aff', '#6d4aff')).toBeCloseTo(1, 5);
	});

	test('is symmetric regardless of argument order', () => {
		expect(contrastRatio('#1b143f', '#ffffff')).toBeCloseTo(contrastRatio('#ffffff', '#1b143f'), 5);
	});

	test('the default theme text colour (#1b143f) on the default surface (#ffffff) passes WCAG AA (>= 4.5:1)', () => {
		expect(contrastRatio('#1b143f', '#ffffff')).toBeGreaterThanOrEqual(4.5);
	});

	test('the default accent colour (#6d4aff) on the default surface (#ffffff) passes WCAG AA (>= 4.5:1)', () => {
		expect(contrastRatio('#6d4aff', '#ffffff')).toBeGreaterThanOrEqual(4.5);
	});

	test('returns null when either colour is unparseable', () => {
		expect(contrastRatio('nope', '#ffffff')).toBeNull();
	});
});

describe('the "Edit Colours" dialog contrast warning', () => {
	beforeEach(() => {
		document.body.innerHTML = settingsPageHtml();
		loadAdminScript();
	});

	function warning() {
		return document.querySelector('[data-cf-contrast-warning]');
	}

	test('is hidden for the (passing) default colour pair', () => {
		expect(warning().hidden).toBe(true);
		expect(warning().textContent).toBe('');
	});

	test('appears when the text colour is edited to a pair that fails 4.5:1', () => {
		var textInput = document.querySelector('[name="theme_text_color"]');
		textInput.value = '#f0f0f5'; // near-white text on a near-white (#ffffff) surface.
		textInput.dispatchEvent(new Event('input'));

		expect(warning().hidden).toBe(false);
		expect(warning().textContent).toMatch(/Text vs\. surface contrast is/);
		expect(warning().getAttribute('role')).toBe('status');
	});

	test('clears again once the pair is fixed back to a passing combination', () => {
		var textInput = document.querySelector('[name="theme_text_color"]');
		textInput.value = '#f0f0f5';
		textInput.dispatchEvent(new Event('input'));
		expect(warning().hidden).toBe(false);

		textInput.value = '#1b143f';
		textInput.dispatchEvent(new Event('input'));

		expect(warning().hidden).toBe(true);
		expect(warning().textContent).toBe('');
	});

	test('also flags a failing accent vs. surface pair', () => {
		var accentInput = document.querySelector('[name="theme_accent_color"]');
		var surfaceInput = document.querySelector('[name="theme_surface_color"]');
		surfaceInput.value = '#6d4aff';
		accentInput.value = '#7d5aff'; // barely-different purple-on-purple: fails contrast.
		accentInput.dispatchEvent(new Event('input'));

		expect(warning().hidden).toBe(false);
		expect(warning().textContent).toMatch(/Accent vs\. surface contrast is/);
	});
});
