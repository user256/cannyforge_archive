const { computeDeviceScale, nextDirtyState } = require('../../assets/js/admin.js');

describe('computeDeviceScale', () => {
	test('desktop (no target width) always scales 1:1', () => {
		expect(computeDeviceScale(300, null)).toBe(1);
		expect(computeDeviceScale(9999, null)).toBe(1);
	});

	test('shrinks a wider device canvas to fit a narrower container', () => {
		// A 768px-wide "tablet" canvas inside a 340px-wide preview panel.
		expect(computeDeviceScale(340, 768)).toBeCloseTo(340 / 768);
	});

	test('never scales up past 1, even when the container is roomier than the device width', () => {
		expect(computeDeviceScale(1000, 375)).toBe(1);
	});

	test('treats a zero/negative container width as scale 1 (nothing to compute yet)', () => {
		expect(computeDeviceScale(0, 768)).toBe(1);
		expect(computeDeviceScale(-10, 768)).toBe(1);
	});
});

describe('nextDirtyState', () => {
	test('input/change mark the form dirty', () => {
		expect(nextDirtyState('input')).toBe(true);
		expect(nextDirtyState('change')).toBe(true);
	});

	test('reset marks the form clean', () => {
		expect(nextDirtyState('reset')).toBe(false);
	});

	test('unrelated event types are a no-op', () => {
		expect(nextDirtyState('submit')).toBeNull();
		expect(nextDirtyState('click')).toBeNull();
	});
});
