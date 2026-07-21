document.addEventListener('DOMContentLoaded', function () {
	var body = document.body;

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

		var openWizard = function () {
			if (typeof dialog.showModal === 'function') {
				dialog.showModal();
			} else {
				dialog.setAttribute('open', 'open');
			}
			body.classList.add('cannyforge-modal-open');
		};

		var closeWizard = function () {
			if (typeof dialog.close === 'function') {
				dialog.close();
			} else {
				dialog.removeAttribute('open');
			}
			body.classList.remove('cannyforge-modal-open');
		};

		openers.forEach(function (button) {
			button.addEventListener('click', openWizard);
		});

		closers.forEach(function (button) {
			button.addEventListener('click', closeWizard);
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
				closeWizard();
			}
		});

		dialog.addEventListener('close', function () {
			body.classList.remove('cannyforge-modal-open');
		});

		if (ga4Toggle) {
			ga4Toggle.addEventListener('change', syncGa4);
			syncGa4();
		}

		if (copyButton && callbackCode && navigator.clipboard && navigator.clipboard.writeText) {
			copyButton.addEventListener('click', function () {
				navigator.clipboard.writeText(callbackCode.textContent || '');
				copyButton.textContent = 'Copied';
				window.setTimeout(function () {
					copyButton.textContent = 'Copy Redirect URI';
				}, 1600);
			});
		}

		if ('1' === dialog.getAttribute('data-cf-google-wizard-auto-open')) {
			openWizard();
		}
	});

	// UI Navigation and Accordion Sync
	var navLinks = document.querySelectorAll('.cf-app-nav a');
	var accordions = document.querySelectorAll('.cf-accordion');
	var contentTab = document.getElementById('tab-content');

	navLinks.forEach(function (link) {
		link.addEventListener('click', function (e) {
			e.preventDefault();
			var targetId = this.getAttribute('href').replace('#tab-', '');

			// Update active state in nav
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

	// Accordion toggle updates nav active state
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

	// Toggle Sidebar Navigation
	var navToggle = document.getElementById('cf-nav-toggle');
	var appContainer = document.querySelector('.cf-app-container');
	if (navToggle && appContainer) {
		navToggle.addEventListener('click', function () {
			appContainer.classList.toggle('nav-collapsed');
		});
	}

	// Toggle Live Preview
	var previewToggle = document.getElementById('cf-preview-toggle');
	if (previewToggle && appContainer) {
		previewToggle.addEventListener('click', function () {
			appContainer.classList.toggle('preview-hidden');
		});
	}
});
