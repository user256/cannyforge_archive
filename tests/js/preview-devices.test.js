const { settingsPageHtml, loadAdminScript } = require('./fixture');

/**
 * jsdom reports every element's layout box as 0x0 (it doesn't run a real
 * layout engine), so clientWidth/clientHeight are stubbed here to exercise
 * the actual scale-fitting math the real preview panel would trigger.
 */
function stubFrameSize(width, height) {
	var frame = document.getElementById('cf-preview-frame');
	Object.defineProperty(frame, 'clientWidth', { value: width, configurable: true });
	Object.defineProperty(frame, 'clientHeight', { value: height, configurable: true });
	return frame;
}

describe('preview device-size toggle', () => {
	beforeEach(() => {
		document.body.innerHTML = settingsPageHtml();
		stubFrameSize(340, 600);
		loadAdminScript();
	});

	function button(device) {
		return document.querySelector('[data-cf-preview-device="' + device + '"]');
	}

	test('desktop starts pressed; the frame fills the panel with no forced size', () => {
		expect(button('desktop').getAttribute('aria-pressed')).toBe('true');
		expect(button('tablet').getAttribute('aria-pressed')).toBe('false');
	});

	test('choosing Tablet updates aria-pressed on all three buttons exclusively', () => {
		button('tablet').click();

		expect(button('desktop').getAttribute('aria-pressed')).toBe('false');
		expect(button('tablet').getAttribute('aria-pressed')).toBe('true');
		expect(button('mobile').getAttribute('aria-pressed')).toBe('false');
	});

	test('choosing Tablet records the device on the frame and scales the iframe to fit', () => {
		button('tablet').click();

		var frame = document.getElementById('cf-preview-frame');
		var iframe = frame.querySelector('iframe');

		expect(frame.getAttribute('data-device')).toBe('tablet');
		expect(iframe.style.width).toBe('768px');
		// 340px available / 768px target device width.
		expect(iframe.style.transform).toBe('scale(' + (340 / 768) + ')');
	});

	test('choosing Desktop again removes the fixed device size', () => {
		button('mobile').click();
		button('desktop').click();

		var iframe = document.querySelector('#cf-preview-frame iframe');
		expect(iframe.style.width).toBe('100%');
		expect(iframe.style.transform).toBe('none');
	});
});
