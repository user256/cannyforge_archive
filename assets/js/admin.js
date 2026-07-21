/**
 * CannyForge Archive — admin settings page behaviour.
 *
 * Every visible control that isn't a plain server round-trip (a normal form
 * submit, a formaction button) is wired up here. Nothing in the markup
 * relies on inline `onclick` attributes; everything is `data-cf-*` driven so
 * it can be found, wired, and unit tested from one place.
 */
(function (root) {
	'use strict';

	/**
	 * Compute the CSS transform scale that fits a fixed-width "device canvas"
	 * inside an available container width, capped at 1 (never scale up).
	 *
	 * Pulled out as a pure function so it can be unit tested without a DOM.
	 *
	 * @param {number} containerWidth Available width, in pixels.
	 * @param {number|null} deviceWidth Target device width, or null/0 for "fill" (desktop).
	 * @return {number} Scale factor in the range (0, 1].
	 */
	function computeDeviceScale(containerWidth, deviceWidth) {
		if (!deviceWidth || deviceWidth <= 0 || !containerWidth || containerWidth <= 0) {
			return 1;
		}

		return Math.min(1, containerWidth / deviceWidth);
	}

	/**
	 * Derive whether the settings form should be considered "dirty" (has
	 * unsaved edits) from a DOM event type. Kept pure/testable: the caller
	 * supplies the event type string instead of a live Event object.
	 *
	 * @param {string} eventType One of 'input', 'change', 'reset', 'submit'.
	 * @return {boolean|null} true = dirty, false = clean, null = no change.
	 */
	function nextDirtyState(eventType) {
		if ('input' === eventType || 'change' === eventType) {
			return true;
		}

		if ('reset' === eventType) {
			return false;
		}

		return null;
	}

	var DEVICE_WIDTHS = { desktop: null, tablet: 768, mobile: 375 };

	/**
	 * Wire a `<dialog>` element's open/close/backdrop-click/close-on-escape
	 * behaviour. Native `<dialog showModal()/close()>` already returns focus
	 * to the invoking element and closes on Escape, so this only needs to
	 * bind the triggers and the click-outside-to-close affordance.
	 *
	 * @param {HTMLDialogElement} dialog
	 * @param {Element[]} openers
	 * @param {Element[]} closers
	 * @param {Function} [onOpen]
	 * @param {Function} [onClose]
	 */
	function wireDialog(dialog, openers, closers, onOpen, onClose) {
		var body = document.body;

		var openDialog = function () {
			if (typeof dialog.showModal === 'function') {
				dialog.showModal();
			} else {
				dialog.setAttribute('open', 'open');
			}
			body.classList.add('cannyforge-modal-open');
			if (onOpen) {
				onOpen();
			}
		};

		var closeDialog = function () {
			if (typeof dialog.close === 'function') {
				dialog.close();
			} else {
				// No native <dialog> support: emulate the 'close' event a real
				// dialog.close() would fire, so listeners (e.g. the body-class
				// cleanup below) still run consistently.
				dialog.removeAttribute('open');
				dialog.dispatchEvent(new Event('close'));
			}
		};

		openers.forEach(function (button) {
			button.addEventListener('click', openDialog);
		});

		closers.forEach(function (button) {
			button.addEventListener('click', closeDialog);
		});

		dialog.addEventListener('click', function (event) {
			var rect;

			if (event.target !== dialog) {
				return;
			}

			rect = dialog.getBoundingClientRect();
			if (
				event.clientX < rect.left ||
				event.clientX > rect.right ||
				event.clientY < rect.top ||
				event.clientY > rect.bottom
			) {
				closeDialog();
			}
		});

		dialog.addEventListener('close', function () {
			body.classList.remove('cannyforge-modal-open');
			if (onClose) {
				onClose();
			}
		});

		return { open: openDialog, close: closeDialog };
	}

	/**
	 * Wire every `<dialog data-cf-dialog="ID">` against its
	 * `[data-cf-dialog-open="ID"]` openers and the `[data-cf-dialog-close]`
	 * closers found inside it. Used for simple single-purpose dialogs (the
	 * colour picker) that don't need the Google wizard's extra behaviour.
	 */
	function initGenericDialogs() {
		document.querySelectorAll('[data-cf-dialog]').forEach(function (dialog) {
			var id = dialog.getAttribute('data-cf-dialog');
			var openers = Array.prototype.slice.call(
				document.querySelectorAll('[data-cf-dialog-open="' + id + '"]')
			);
			var closers = Array.prototype.slice.call(dialog.querySelectorAll('[data-cf-dialog-close]'));

			wireDialog(dialog, openers, closers);
		});
	}

	function initGoogleWizardDialogs() {
		document.querySelectorAll('[data-cf-google-wizard-dialog]').forEach(function (dialog) {
			var openers = Array.prototype.slice.call(document.querySelectorAll('[data-cf-google-wizard-open]'));
			var closers = Array.prototype.slice.call(dialog.querySelectorAll('[data-cf-google-wizard-close]'));
			var ga4Toggle = dialog.querySelector('[data-cf-google-ga4-toggle]');
			var ga4Panel = dialog.querySelector('[data-cf-google-ga4-fields]');
			var ga4Input = ga4Panel ? ga4Panel.querySelector('input[name="google_ga4_property_id"]') : null;
			var copyButton = dialog.querySelector('[data-cf-google-copy-callback]');
			var callbackCode = dialog.querySelector('[data-cf-google-callback-url]');

			var syncGa4 = function () {
				if (!ga4Toggle || !ga4Panel || !ga4Input) {
					return;
				}

				var enabled = !!ga4Toggle.checked;
				ga4Panel.hidden = !enabled;
				ga4Input.disabled = !enabled;

				if (!enabled) {
					ga4Input.value = '';
				}
			};

			wireDialog(dialog, openers, closers);

			if (ga4Toggle) {
				ga4Toggle.addEventListener('change', syncGa4);
				syncGa4();
			}

			if (copyButton && callbackCode && navigator.clipboard && navigator.clipboard.writeText) {
				// Capture the server-rendered (already localised) label instead of
				// hardcoding the revert text, so only the transient "Copied" state
				// needs its own localised string (ticket 610).
				var copyDefaultLabel = copyButton.textContent;
				var copiedLabel = (root.CannyForgeAdminL10n && root.CannyForgeAdminL10n.copiedLabel) || 'Copied';

				copyButton.addEventListener('click', function () {
					navigator.clipboard.writeText(callbackCode.textContent || '');
					copyButton.textContent = copiedLabel;
					window.setTimeout(function () {
						copyButton.textContent = copyDefaultLabel;
					}, 1600);
				});
			}

			if ('1' === dialog.getAttribute('data-cf-google-wizard-auto-open')) {
				if (typeof dialog.showModal === 'function') {
					dialog.showModal();
				} else {
					dialog.setAttribute('open', 'open');
				}
				document.body.classList.add('cannyforge-modal-open');
			}
		});
	}

	/**
	 * Sync the nav sidebar, tab links, and accordions: clicking a nav link
	 * scrolls to (and, for accordion sections, opens) the matching section;
	 * opening an accordion by any means (click, keyboard) re-activates the
	 * matching nav link.
	 */
	function initNavigation() {
		var navLinks = document.querySelectorAll('.cf-app-nav a');
		var accordions = document.querySelectorAll('.cf-accordion');
		var contentTab = document.getElementById('tab-content');

		navLinks.forEach(function (link) {
			link.addEventListener('click', function (e) {
				e.preventDefault();
				var targetId = this.getAttribute('href').replace('#tab-', '');

				navLinks.forEach(function (l) { l.parentElement.classList.remove('active'); });
				this.parentElement.classList.add('active');

				if (targetId === 'content') {
					if (contentTab) contentTab.scrollIntoView({ behavior: 'smooth' });
				} else {
					var targetAccordion = document.getElementById('accordion-' + targetId);
					if (targetAccordion) {
						targetAccordion.open = true;
						targetAccordion.scrollIntoView({ behavior: 'smooth' });
					}
				}
			});
		});

		accordions.forEach(function (accordion) {
			accordion.addEventListener('toggle', function () {
				if (this.open) {
					var id = this.id.replace('accordion-', '');
					navLinks.forEach(function (l) {
						if (l.getAttribute('href') === '#tab-' + id) {
							navLinks.forEach(function (nl) { nl.parentElement.classList.remove('active'); });
							l.parentElement.classList.add('active');
						}
					});
				}
			});
		});
	}

	/**
	 * Wire the sidebar-collapse and live-preview-panel toggle buttons,
	 * keeping their `aria-expanded` state in sync with what's shown.
	 */
	function initPanelToggles() {
		var navToggle = document.getElementById('cf-nav-toggle');
		var appContainer = document.querySelector('.cf-app-container');
		if (navToggle && appContainer) {
			navToggle.addEventListener('click', function () {
				var collapsed = appContainer.classList.toggle('nav-collapsed');
				navToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
			});
		}

		var previewToggle = document.getElementById('cf-preview-toggle');
		if (previewToggle && appContainer) {
			previewToggle.addEventListener('click', function () {
				var hidden = appContainer.classList.toggle('preview-hidden');
				previewToggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
			});
		}
	}

	/**
	 * Wire the preview device-size buttons (Desktop/Tablet/Mobile): toggles
	 * `aria-pressed`, and scales the preview iframe to approximate the
	 * chosen device width within the fixed-size preview frame.
	 */
	function initPreviewDevices() {
		var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-cf-preview-device]'));
		var frame = document.getElementById('cf-preview-frame');
		if (!buttons.length || !frame) {
			return;
		}
		var iframe = frame.querySelector('iframe');

		var applyDevice = function (device) {
			frame.setAttribute('data-device', device);

			if (!iframe) {
				return;
			}

			var deviceWidth = DEVICE_WIDTHS[device];
			if (!deviceWidth) {
				iframe.style.width = '100%';
				iframe.style.height = '100%';
				iframe.style.transform = 'none';
				return;
			}

			var containerWidth = frame.clientWidth || deviceWidth;
			var containerHeight = frame.clientHeight || 0;
			var scale = computeDeviceScale(containerWidth, deviceWidth);

			iframe.style.width = deviceWidth + 'px';
			iframe.style.height = (scale > 0 ? containerHeight / scale : containerHeight) + 'px';
			iframe.style.transformOrigin = 'top left';
			iframe.style.transform = 'scale(' + scale + ')';
		};

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				var device = button.getAttribute('data-cf-preview-device');

				buttons.forEach(function (b) {
					b.setAttribute('aria-pressed', b === button ? 'true' : 'false');
				});

				applyDevice(device);
			});
		});
	}

	/**
	 * Track whether the settings form has unsaved edits, and keep every
	 * "Draft changes" / "All changes saved" indicator honest and mutually
	 * exclusive. A native form reset (the "Reset to saved values" button)
	 * clears the dirty flag once the browser has actually reset the fields.
	 */
	function initDirtyState() {
		var form = document.getElementById('cf-settings-form');
		var status = document.getElementById('cf-form-status');
		var staleNotice = document.getElementById('cf-preview-stale');
		if (!form || !status) {
			return;
		}

		var icon = status.querySelector('.dashicons');
		var text = status.querySelector('.cf-footer-status-text');
		var savedLabel = (root.CannyForgeAdminL10n && root.CannyForgeAdminL10n.savedLabel) || 'All changes saved';
		var draftLabel = (root.CannyForgeAdminL10n && root.CannyForgeAdminL10n.draftLabel) || 'Draft changes';

		var setDirty = function (dirty) {
			status.setAttribute('data-state', dirty ? 'dirty' : 'saved');

			if (text) {
				text.textContent = dirty ? draftLabel : savedLabel;
			}

			if (icon) {
				icon.classList.toggle('dashicons-saved', !dirty);
				icon.classList.toggle('dashicons-warning', dirty);
			}

			if (staleNotice) {
				staleNotice.hidden = !dirty;
			}
		};

		var handle = function (eventType, deferred) {
			return function () {
				var next = nextDirtyState(eventType);
				if (null === next) {
					return;
				}

				if (deferred) {
					// The browser applies a form reset's field values after
					// this event fires; defer so the state reflects the
					// just-reset (clean) form.
					window.setTimeout(function () { setDirty(next); }, 0);
				} else {
					setDirty(next);
				}
			};
		};

		form.addEventListener('input', handle('input', false));
		form.addEventListener('change', handle('change', false));
		form.addEventListener('reset', handle('reset', true));

		setDirty(false);
	}

	function init() {
		initGenericDialogs();
		initGoogleWizardDialogs();
		initNavigation();
		initPanelToggles();
		initPreviewDevices();
		initDirtyState();
	}

	if ('undefined' !== typeof document) {
		document.addEventListener('DOMContentLoaded', init);
	}

	// Exposed for unit tests only (no-op in the browser: `module` is undefined there).
	if ('undefined' !== typeof module && module.exports) {
		module.exports = {
			computeDeviceScale: computeDeviceScale,
			nextDirtyState: nextDirtyState,
			wireDialog: wireDialog,
			init: init,
		};
	}
})(typeof window !== 'undefined' ? window : this);
