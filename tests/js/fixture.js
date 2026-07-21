/**
 * A trimmed-down copy of the markup SettingsView/ModeSettingsPanelView render,
 * covering exactly the ids/classes/data-attributes admin.js binds to. Kept in
 * one place so every test file exercises the same real-shaped DOM.
 */
function settingsPageHtml() {
	return `
		<div class="cf-app-container preview-hidden">
			<header class="cf-app-header">
				<div class="cf-header-left">
					<button type="button" id="cf-nav-toggle" aria-label="Toggle navigation" aria-expanded="true" aria-controls="cf-app-sidebar"><span class="dashicons dashicons-menu"></span></button>
					<h1>CannyForge Archive</h1>
				</div>
				<div class="cf-header-right">
					<button type="button" id="cf-preview-toggle" aria-expanded="false" aria-controls="cf-preview-panel">Live Preview</button>
					<button type="submit" class="cf-btn cf-btn-primary" form="cf-settings-form">Save changes</button>
				</div>
			</header>

			<form method="post" class="cf-app-form" id="cf-settings-form">
				<div class="cf-app-body">
					<aside class="cf-app-sidebar" id="cf-app-sidebar">
						<ul class="cf-app-nav">
							<li class="active"><a href="#tab-content">Content</a></li>
							<li><a href="#tab-display">Display</a></li>
						</ul>
					</aside>

					<main class="cf-app-main">
						<div id="tab-content" class="cf-tab-section active">
							<div class="cf-mode-cards">
								<label class="cf-mode-card">
									<input type="radio" name="mode" value="news" class="cf-visually-hidden">
									<div class="cf-radio-circle"><div class="cf-radio-dot"></div></div>
								</label>
								<label class="cf-mode-card">
									<input type="radio" name="mode" value="blog" class="cf-visually-hidden" checked>
									<div class="cf-radio-circle"><div class="cf-radio-dot"></div></div>
								</label>
								<label class="cf-mode-card">
									<input type="radio" name="mode" value="hybrid" class="cf-visually-hidden">
									<div class="cf-radio-circle"><div class="cf-radio-dot"></div></div>
								</label>
							</div>
							<input type="text" name="seo_title" value="">
						</div>

						<details class="cf-accordion" id="accordion-display">
							<summary class="cf-accordion-summary">Display</summary>
							<div class="cf-accordion-body">
								<button type="button" class="button button-secondary" data-cf-dialog-open="colors">Edit Colours</button>
								<dialog id="cf-colors-modal" data-cf-dialog="colors" aria-labelledby="cf-colors-modal-title">
									<button type="button" class="cannyforge-modal__close" aria-label="Close" data-cf-dialog-close>&times;</button>
									<h3 id="cf-colors-modal-title">Edit Colours</h3>
									<input type="color" name="theme_accent_color" value="#6d4aff">
									<input type="color" name="theme_surface_color" value="#ffffff">
									<input type="color" name="theme_text_color" value="#1b143f">
									<input type="color" name="theme_border_color" value="#d8dbe8">
									<p class="cf-contrast-warning" data-cf-contrast-warning role="status" hidden></p>
									<button type="button" class="button button-primary" data-cf-dialog-close>Done</button>
								</dialog>
							</div>
						</details>
					</main>

					<aside class="cf-app-preview" id="cf-preview-panel">
						<div class="cf-preview-header">
							<p class="cf-preview-stale" id="cf-preview-stale" hidden>You have unsaved changes that are not shown below yet.</p>
						</div>
						<div class="cf-preview-controls" role="group" aria-label="Preview device size">
							<button type="button" class="cf-preview-device" data-cf-preview-device="desktop" aria-pressed="true"><span class="cf-visually-hidden">Desktop</span></button>
							<button type="button" class="cf-preview-device" data-cf-preview-device="tablet" aria-pressed="false"><span class="cf-visually-hidden">Tablet</span></button>
							<button type="button" class="cf-preview-device" data-cf-preview-device="mobile" aria-pressed="false"><span class="cf-visually-hidden">Mobile</span></button>
						</div>
						<div class="cf-preview-frame" id="cf-preview-frame" data-device="desktop">
							<iframe src="http://example.test/archive/" title="Archive preview"></iframe>
						</div>
					</aside>
				</div>

				<footer class="cf-app-footer">
					<div class="cf-footer-status" id="cf-form-status" data-state="saved" aria-live="polite">
						<span class="dashicons dashicons-saved"></span>
						<span class="cf-footer-status-text">All changes saved</span>
					</div>
					<div class="cf-footer-actions">
						<button type="reset" class="cf-btn cf-btn-text" id="cf-reset-btn">Reset to saved values</button>
						<button type="submit" id="cf-save-btn">Save changes</button>
					</div>
				</footer>
			</form>
		</div>
	`;
}

// admin.js is required once and cached: it self-registers exactly one
// `document.addEventListener('DOMContentLoaded', init)` at require time. Every
// test instead calls the exported `init()` directly against the DOM it just
// built — re-requiring per test (even with jest.resetModules()) would stack
// up additional 'DOMContentLoaded' listeners on the shared jsdom `document`,
// so every later test's click would fire every earlier test's handlers too.
// eslint-disable-next-line global-require
var adminModule = require('../../assets/js/admin.js');

/**
 * Wire up admin.js's behaviour against the DOM currently in `document.body`,
 * mirroring what the browser does once for an enqueued classic script.
 */
function loadAdminScript() {
	adminModule.init();
	return adminModule;
}

module.exports = { settingsPageHtml: settingsPageHtml, loadAdminScript: loadAdminScript };
