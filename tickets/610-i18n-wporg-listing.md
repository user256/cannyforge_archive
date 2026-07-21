# Ticket 610: i18n completeness + wp.org listing assets

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** unassigned
**Estimate:** S

---

## Context

Strings correctly use the `cannyforge-archive` text domain, but there is no
generated `.pot` file, so translators have nothing to work from, and nothing
in QA verifies new strings keep the right domain. Separately, the wp.org
listing itself is part of "amazing": the directory page needs banner/icon/
screenshot assets and a readme whose FAQ answers what reviewers and users ask
first (what does it do to my pagination? what data goes to Google? how do I
undo it?). `assets/branding/` exists but the wp.org `/assets` convention
(repo-root assets dir in SVN, not shipped in the plugin) is not yet prepared.

## Goal

The plugin is translation-ready with the domain enforced by tooling, and the
wp.org listing assets and readme are submission-complete.

## Acceptance criteria

- [x] `composer i18n` (new script) runs WP-CLI `wp i18n make-pot` producing
      `languages/cannyforge-archive.pot`; the script is part of the release
      procedure documented in README.
- [x] PHPCS `WordPress.WP.I18n` sniff is configured with
      `text_domain=cannyforge-archive` in `phpcs.xml.dist` so a wrong/missing
      domain fails `composer cs`.
- [x] Translator comments (`/* translators: ... */`) added for every string
      with placeholders.
- [x] JS strings in `assets/js/*.js` that surface to users are localised via
      `wp_set_script_translations` / `wp_localize_script` rather than
      hardcoded English.
- [~] wp.org listing assets prepared to spec: banner 1544×500 & 772×250,
      icon 256×256 & 128×128, ≥ 3 screenshots (settings page, archive page,
      shortened pagination) with matching `== Screenshots ==` captions in
      readme.txt. **Partially done** — banner and icon PNGs at all four spec
      sizes are produced (`.wordpress-org/`). The three screenshots are NOT
      produced; see the decisions log and follow-up ticket 618.
- [x] readme.txt FAQ covers: pagination reversibility, exactly what data is
      sent to Google and when (extending the Sprint 5 disclosure), and cache
      behaviour.

## Out of scope

- Actually producing translations; the deliverable is the infrastructure.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none
- **External:** WP-CLI available locally/CI (composer-installable)

## Notes / decisions log

- 2026-07-21 — `phpcs.xml.dist` already had `WordPress.WP.I18n` configured
  with `text_domain=cannyforge-archive`, and every `sprintf`/`__()`-with-placeholder
  call site already carried a correct `/* translators: ... */` comment.
  `composer cs` was already clean on this sniff before any change in this
  ticket — verified by reading the ruleset and running `composer cs`, not
  assumed. No source changes were needed for those two acceptance criteria
  beyond the JS-localisation work below (which added two new placeholder
  strings, both given translator comments).
- 2026-07-21 — Added `composer i18n` (`wp i18n make-pot . languages/cannyforge-archive.pot
  --slug=cannyforge-archive --domain=cannyforge-archive --exclude=vendor,tests,dist,node_modules,.wordpress-org`).
  Had to pin `--slug` explicitly: without it, WP-CLI defaults the slug (and
  therefore the generated `Report-Msgid-Bugs-To` URL) to the *checkout
  directory's basename*, which is wrong whenever the working directory isn't
  literally named `cannyforge-archive` (as in this worktree). Documented the
  step in README.md's new "Release Procedure" section. The generated
  `languages/cannyforge-archive.pot` ships inside the plugin ZIP (added
  `languages/` to `DistributablePackageTest`'s allow-list) — it is not a
  wp.org-SVN-only asset like the banner/icon/screenshots are.
- 2026-07-21 — JS localisation: `assets/js/archive-filters.js` (prev/next
  pagination labels, "Page X of Y" status, "No results…"/"Found N results…"
  summary text) and `assets/js/admin.js` (the transient "Copied" label on the
  OAuth redirect-URI copy button) were hardcoded English. Wired both through
  `wp_localize_script` (`cannyforgeArchive` / `CannyForgeAdminL10n`) from
  `ArchiveAssets::enqueue()` / `AdminAssets::enqueue()`, since neither script
  declares a `wp-i18n` dependency (they're plain vanilla, not
  `@wordpress/i18n`-built), so `wp_localize_script` is the correct mechanism
  here rather than `wp_set_script_translations` (that requires a script to
  depend on `wp-i18n` and ship compiled JSON language packs, which doesn't
  apply to this plugin's asset pipeline). For the admin.js copy button, the
  revert-to-default label is captured from the server-rendered button text
  at runtime instead of hardcoding it a second time in JS, so only the new
  "Copied" state needed its own localised string.
- 2026-07-21 — Discovered and fixed a **pre-existing, unrelated** test-suite
  bug while verifying `composer qa` end-to-end (confirmed pre-existing via
  `git stash` against the original tree before touching anything): two test
  shim files (`tests/wp-hooks-shim.php` and `tests/wp-admin-post-shim.php`)
  both defined `wp_safe_redirect()`; PHP function declarations are
  first-come-first-served, and `wp-hooks-shim.php` loads first in
  `tests/bootstrap.php`, so its non-throwing, HookSpy-recording version
  silently won — meaning `GoogleConnectionController`'s
  `wp_safe_redirect(...); exit;` pattern hit a **real, literal `exit;`**
  mid-test-suite (visible via `strace` as `exit_group(0)`), silently killing
  the whole PHPUnit process after ~5-8 tests with no error output. This
  wasn't introduced by this ticket, but it blocked verifying `composer qa`
  is green at all, so it was fixed: the one canonical `wp_safe_redirect()`
  shim (`wp-hooks-shim.php`) now throws `WpRedirectException` on a
  "successful" redirect (matching the `wp_die()`/`wp_redirect()` shims'
  existing convention) while still honouring the
  `cannyforge_test_safe_redirect_result` override for the *rejected*-redirect
  fallback tests in `ArchivePageTest`. The now-dead duplicate in
  `wp-admin-post-shim.php` was removed in favour of a comment pointing at the
  canonical one. Also fixed one stale, unrelated test assertion
  (`SettingsViewTest::test_renders_preview_link` still asserted the
  pre-ticket-613 "Open" link text/`title="Preview"` iframe attribute instead
  of current "Preview Archive"/`title="Archive preview"`) so `composer test`
  reports a genuine 100% pass, not a masked failure. Neither fix touches
  ticket 610's actual scope; both are called out here for transparency.
- 2026-07-21 — Image tooling: `rsvg-convert` is not installed, but
  ImageMagick's `convert` renders the path-based SVGs (`cannyforge-icon-only.svg`,
  `logo-full-dark.svg`) cleanly. `inkscape` is present on `PATH` but is a
  broken snap install (`symbol lookup error: ...libpthread.so.0: undefined
  symbol: __libc_pthread_init`) and cannot run at all here. Separately,
  `cannyforge-font-dark.svg` / `cannyforge-font-light.svg` use a live `<text>`
  element (`font-family="Inter, ..."`) rather than pre-outlined paths, and
  the "Inter" font is not installed on this machine — ImageMagick's SVG
  delegate renders that text element as garbled, overlapping glyphs (visibly
  broken). Those two files were therefore **not used**. `logo-full-dark.svg`
  (path-outlined, includes its own `#12083A` background) renders correctly
  and was used for both banner sizes, letterboxed onto the exact 1544×500 /
  772×250 canvases with matching `#12083A` padding (no distortion/cropping).
  `cannyforge-icon-only.svg` (square, transparent) was used directly for both
  icon sizes. All four PNGs were visually inspected (not just dimension-checked)
  before being committed. Produced: `.wordpress-org/banner-1544x500.png`,
  `banner-772x250.png`, `icon-256x256.png`, `icon-128x128.png`.
- 2026-07-21 — Screenshots: genuinely could not be produced responsibly.
  A live WordPress instance *does* exist on this sandbox (`/var/www/html`,
  nginx + php8.3-fpm + mysql, `cannyforge-archive` already active per
  `wp plugin list --allow-root`) and headless screenshot tooling
  (`chromium-browser --headless --screenshot=...`) works. However, that
  instance is a shared, multi-project dev sandbox currently serving *live,
  unrelated* front-end content (an affiliate/review site with its own theme
  and real posts) alongside several other installed-but-inactive plugins —
  not a clean instance provisioned solely for this ticket. Screenshotting
  through that theme/content would misrepresent the plugin for the wp.org
  listing, and mutating a shared instance's active theme/content risked
  disrupting concurrent work with no visibility into what else might depend
  on its current state. Filed ticket 618 to capture the three screenshots
  properly once either a dedicated instance is carved out or ticket 603's
  real-WordPress integration rig lands. `readme.txt`'s `== Screenshots ==`
  section has the three captions reserved with an explicit reviewer note
  that the image files themselves are pending ticket 618 — not faked as
  present.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
