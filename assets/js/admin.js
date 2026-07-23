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
	 * The WCAG 2.2 AA contrast-ratio threshold for normal-size text (ticket 609).
	 */
	var WCAG_AA_TEXT_CONTRAST = 4.5;

	/**
	 * Parse a 3- or 6-digit `#hex` colour into 0-255 RGB channels.
	 *
	 * Pulled out as a pure function so the contrast maths can be unit tested
	 * without a DOM/`<input type="color">`.
	 *
	 * @param {string} hex A `#rgb` or `#rrggbb` colour string.
	 * @return {{r: number, g: number, b: number}|null} Parsed channels, or null if unparseable.
	 */
	function hexToRgb(hex) {
		var normalized = String(hex || '').trim().replace(/^#/, '');

		if (3 === normalized.length) {
			normalized = normalized.replace(/(.)/g, '$1$1');
		}

		if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
			return null;
		}

		return {
			r: parseInt(normalized.substring(0, 2), 16),
			g: parseInt(normalized.substring(2, 4), 16),
			b: parseInt(normalized.substring(4, 6), 16),
		};
	}

	/**
	 * WCAG relative luminance of an sRGB colour (0-1).
	 *
	 * @see https://www.w3.org/TR/WCAG22/#dfn-relative-luminance
	 * @param {{r: number, g: number, b: number}} rgb 0-255 RGB channels.
	 * @return {number} Relative luminance.
	 */
	function relativeLuminance(rgb) {
		var channel = function (value) {
			var c = value / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		};

		return 0.2126 * channel(rgb.r) + 0.7152 * channel(rgb.g) + 0.0722 * channel(rgb.b);
	}

	/**
	 * WCAG contrast ratio between two `#hex` colours, from 1 (no contrast) to 21.
	 *
	 * @see https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
	 * @param {string} hexA First colour.
	 * @param {string} hexB Second colour.
	 * @return {number|null} Contrast ratio, or null if either colour is unparseable.
	 */
	function contrastRatio(hexA, hexB) {
		var a = hexToRgb(hexA);
		var b = hexToRgb(hexB);

		if (!a || !b) {
			return null;
		}

		var luminanceA = relativeLuminance(a);
		var luminanceB = relativeLuminance(b);
		var lighter = Math.max(luminanceA, luminanceB);
		var darker = Math.min(luminanceA, luminanceB);

		return (lighter + 0.05) / (darker + 0.05);
	}

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

	/**
	 * Wire every `[data-cf-copy="<selector>"]` button to copy the value of
	 * the input the selector points at (the Google wizard's redirect URI).
	 * The server-rendered (already localised) label is captured for the
	 * revert, so only the transient "Copied" state needs its own string.
	 */
	function initCopyButtons() {
		if (!navigator.clipboard || !navigator.clipboard.writeText) {
			return;
		}

		document.querySelectorAll('[data-cf-copy]').forEach(function (button) {
			var target = document.querySelector(button.getAttribute('data-cf-copy'));
			if (!target) {
				return;
			}

			var defaultLabel = button.textContent;
			var copiedLabel = (root.CannyForgeAdminL10n && root.CannyForgeAdminL10n.copiedLabel) || 'Copied';

			button.addEventListener('click', function () {
				navigator.clipboard.writeText('value' in target ? target.value : target.textContent || '');
				button.textContent = copiedLabel;
				window.setTimeout(function () {
					button.textContent = defaultLabel;
				}, 1600);
			});
		});
	}

	/**
	 * Auto-select the contents of read-only reference inputs (the wizard's
	 * redirect URI) on focus, so a manual copy grabs the whole value.
	 */
	function initSelectOnFocus() {
		document.querySelectorAll('[data-cf-select-on-focus]').forEach(function (input) {
			input.addEventListener('focus', function () {
				input.select();
			});
		});
	}

	/**
	 * Add selected, human-readable Search Console pages to the Blog URL
	 * textarea. This is intentionally a client-side curation action: the
	 * normal settings save remains the single place where the edited URL list
	 * is persisted.
	 */
	function initSearchConsoleCurator() {
		document.querySelectorAll('[data-cf-add-search-console-pages]').forEach(function (button) {
			var targetSelector = button.getAttribute('data-target');
			var target = targetSelector ? document.querySelector(targetSelector) : null;
			var status = button.parentElement ? button.parentElement.querySelector('[data-cf-search-console-status]') : null;

			if (!target) {
				return;
			}

			button.addEventListener('click', function () {
				var urls = target.value.split(/\r?\n/).map(function (url) { return url.trim(); }).filter(Boolean);
				var seen = Object.create(null);
				var added = 0;

				urls.forEach(function (url) { seen[url] = true; });

				button.closest('.cf-search-console-curator').querySelectorAll('[data-cf-search-console-page]:checked').forEach(function (checkbox) {
					var url = (checkbox.getAttribute('data-url') || '').trim();

					if (url && !seen[url]) {
						urls.push(url);
						seen[url] = true;
						added += 1;
					}

					checkbox.checked = false;
				});

				if (0 === added) {
					if (status) {
						status.textContent = (root.CannyForgeAdminL10n && root.CannyForgeAdminL10n.curatorNoneSelected) || 'Select at least one page first.';
					}
					return;
				}

				target.value = urls.join('\n');
				target.dispatchEvent(new Event('input', { bubbles: true }));
				if (status) {
					status.textContent = ((root.CannyForgeAdminL10n && root.CannyForgeAdminL10n.curatorAdded) || 'Added %d page(s) to curated URLs.').replace('%d', String(added));
				}
			});
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

	/**
	 * Wire a live WCAG AA contrast warning into the "Edit Colours" dialog
	 * (ticket 609): as the site owner edits the text/accent vs. surface
	 * colour pickers, show a plain-language warning the moment a pair drops
	 * below the 4.5:1 ratio normal text needs, and clear it once they fix it.
	 * The warning region is `role="status"` (implicit `aria-live="polite"`),
	 * so screen-reader users get the same feedback sighted users see.
	 */
	function initContrastWarning() {
		var dialog = document.getElementById('cf-colors-modal');
		var warning = dialog ? dialog.querySelector('[data-cf-contrast-warning]') : null;
		var textInput = dialog ? dialog.querySelector('[name="theme_text_color"]') : null;
		var surfaceInput = dialog ? dialog.querySelector('[name="theme_surface_color"]') : null;
		var accentInput = dialog ? dialog.querySelector('[name="theme_accent_color"]') : null;

		if (!dialog || !warning || !textInput || !surfaceInput) {
			return;
		}

		var l10n = root.CannyForgeAdminL10n || {};

		var formatRatio = function (ratio) {
			return Math.round(ratio * 100) / 100;
		};

		var pairWarning = function (template, fallback, foreground) {
			var ratio = contrastRatio(foreground, surfaceInput.value);
			if (null === ratio || ratio >= WCAG_AA_TEXT_CONTRAST) {
				return null;
			}
			return (template || fallback).replace('%s', String(formatRatio(ratio)));
		};

		var update = function () {
			var messages = [];
			var textMessage = pairWarning(
				l10n.contrastTextWarning,
				'Text vs. surface contrast is %s:1 — WCAG AA requires at least 4.5:1.',
				textInput.value
			);
			if (textMessage) {
				messages.push(textMessage);
			}

			if (accentInput) {
				var accentMessage = pairWarning(
					l10n.contrastAccentWarning,
					'Accent vs. surface contrast is %s:1 — WCAG AA requires at least 4.5:1.',
					accentInput.value
				);
				if (accentMessage) {
					messages.push(accentMessage);
				}
			}

			warning.textContent = messages.join(' ');
			warning.hidden = 0 === messages.length;
		};

		[textInput, surfaceInput, accentInput].forEach(function (input) {
			if (input) {
				input.addEventListener('input', update);
			}
		});

		update();
	}

	function init() {
		initGenericDialogs();
		initCopyButtons();
		initSelectOnFocus();
		initSearchConsoleCurator();
		initNavigation();
		initPanelToggles();
		initPreviewDevices();
		initDirtyState();
		initContrastWarning();
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
			hexToRgb: hexToRgb,
			relativeLuminance: relativeLuminance,
			contrastRatio: contrastRatio,
			init: init,
		};
	}
})(typeof window !== 'undefined' ? window : this);
