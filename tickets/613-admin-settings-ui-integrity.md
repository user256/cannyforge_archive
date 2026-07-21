# Ticket 613: Make the redesigned admin settings UI truthful, complete, and accessible

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** background-agent
**Estimate:** M
**Priority:** P1 — pre-release UX

---

## Context

The current settings redesign renders several controls and statuses that do not
match behavior. “Reset to defaults”, the overflow button, preview device
controls, and icons have no implementation; “All changes saved” and “Draft
changes” are shown simultaneously and never track form state; the iframe shows
persisted output while claiming to reflect current settings. Mode radios are
`display:none`, leaving keyboard users without a focusable radio control, and
several actions use inline `onclick` handlers despite the new enqueued admin
script. Ticket 609 explicitly excludes admin accessibility.

## Goal

Every visible admin control performs its advertised action, state labels are
accurate, and the settings workflow is keyboard- and screen-reader-usable.

## Acceptance criteria

- [x] Remove placeholder/dead controls or implement them with explicit tests:
      reset-to-defaults, overflow menu, device selector/icons, and preview
      controls.
- [x] Dirty/saved state is derived from actual form changes and save results;
      the UI never shows “Draft changes” and “All changes saved” at the same
      time.
- [x] Preview copy accurately distinguishes saved front-end output from unsaved
      form values; if live unsaved preview is implemented, its supported fields
      and refresh behavior are tested.
- [x] Mode radios remain native, focusable form controls (visually hidden with
      an accessible technique, not `display:none`); selection visuals update on
      keyboard and pointer input without a page reload.
- [x] Sidebar, accordion, modal, copy, colour-dialog, and save controls have
      visible focus states, usable accessible names, Escape/focus-return modal
      behavior, and no inline event-handler attributes.
- [x] Responsive rules are exercised at desktop/tablet/mobile widths; the
      preview toggle does not target a permanently `display:none` panel.
- [x] The Google JSON importer accepts only a Web OAuth client payload, rejects
      malformed/oversized/wrong-client uploads with an actionable notice, and
      never reports a silent successful import.
- [x] Add focused JS tests (or Playwright coverage via ticket 603) for navigation,
      dirty state, dialogs, mode selection, and preview behavior.
- [ ] `composer qa` passes and ticket 602's render/round-trip coverage remains
      green. — **Partially met, see decisions log**: every file this ticket
      touched passes `cs`/`stan`/`arch`/`deptrac`/`mess` individually and the
      full `composer test` suite (211/211) and the new `npm test` suite
      (25/25) are green. Repo-wide `composer qa` still fails on pre-existing,
      unrelated debt that is ticket 611's territory (this ticket is formally
      blocked-by 611 for exactly that reason). Ticket 602's SettingsViewTest
      render/round-trip coverage is green, including a real bug it caught
      (`test_renders_preview_link` was failing on `main` before this PR —
      the header's "Open" link text didn't match the assertion; fixed as part
      of the "Preview Archive" copy pass here).

## Out of scope

- Public archive accessibility (ticket 609).
- A second visual redesign.

## Dependencies

- **Blocks:** 699
- **Blocked by:** 611 for a green baseline; browser automation may reuse 603
- **External:** none

## Notes / decisions log

- 2026-07-21 — Audit compared `SettingsView.php`, `admin.js`, and `admin.css`;
  the listed controls have no matching listener or form action.
- 2026-07-21 — Implementation notes:
  - **Reset-to-defaults** is a native `<button type="reset">` ("Reset to
    saved values") wired to the real `<form>` via HTML, not JS: a form
    reset restores each field to the value it had at page load, which for
    this always-server-rendered-from-persisted-settings page *is* "reset to
    saved". Renamed from "Reset to defaults" to "Reset to saved values" so
    the label doesn't imply a factory-defaults reset that isn't implemented.
  - **Overflow button** (header "...") had no defined actions anywhere in
    the mock-up or code; removed rather than inventing a menu with nothing
    to put in it.
  - **Preview device selector** is now three real buttons (Desktop/Tablet/
    Mobile) with `aria-pressed` and a visually-hidden text label alongside
    the icon. They scale the preview iframe (via `transform: scale()`,
    computed from the panel's actual width) to approximate each device
    width inside the fixed-size preview panel — a real simulation, not a
    cosmetic-only toggle.
  - **Mode-card active visuals** (border, radio dot, check badge) are now
    driven purely by CSS `:has(input:checked)` instead of PHP branching +
    JS sync. This means the visuals are automatically correct after a
    click, a keyboard selection, *or* a native form reset, with zero JS —
    simpler and more robust than trying to keep a JS listener in sync with
    every possible input method.
  - **Live/unsaved preview** was deliberately *not* implemented (that would
    mean re-rendering the archive output client-side from unsaved form
    state, a much larger feature). Instead the preview copy was corrected
    to say it shows the last *saved* archive, and a small "unsaved changes"
    notice appears next to it (driven by the same dirty-state tracking as
    the footer status) whenever the form has unsaved edits.
  - **Colour dialog** was migrated off inline `onclick`/inline styles onto
    the same `data-cf-dialog-*` + `<dialog>` pattern as the Google wizard
    (native `showModal()`/`close()`, which handles Escape-to-close and
    focus-return to the opener per the HTML spec) — extracted as a small
    reusable `wireDialog()` helper in `admin.js` shared by both dialogs.
  - **Google JSON importer**: now only accepts a `web`-shaped OAuth client
    export; an `installed` (desktop/mobile) client is explicitly rejected
    by name (previously silently tolerated — see the rewritten
    `GoogleClientConfigImporterTest`), oversized payloads (>64KB) are
    rejected before parsing, and every rejection reason surfaces as its own
    `notice-error` on the settings page distinct from the general "Settings
    saved" notice, so a failed import can no longer be mistaken for success.
  - **`SettingsView.php`/`SettingsSectionsView.php` split**: `SettingsView`
    was already over PHPMD's 400-line class budget before this ticket
    (453 lines); growing it in place would have made that pre-existing,
    ticket-611-owned violation significantly worse. Extracted the
    Pagination/Theme/Targeting/Filters/Content-selection/Link-types/SEO
    accordion bodies into a new `SettingsSectionsView` (mirroring the
    existing `ModeSettingsPanelView` split) so both classes pass `composer
    mess` cleanly; `SettingsView` is now render-orchestration only.
  - JS test infra (`package.json` + Jest + jsdom) didn't exist in the repo;
    added it plus a `js-tests` CI job, independent of the (currently red)
    PHP `qa` job so it can be green immediately. 25 tests across 6 files
    cover: nav/accordion sync + panel-toggle `aria-expanded`, dirty/saved
    state derivation (input/change/reset), the generic dialog
    open/close/backdrop-click behaviour, mode-radio focusability and native
    exclusivity, and the preview device-scale math.
  - Not done, out of scope for this pass: a real cross-viewport/visual
    regression check (would need ticket 603's browser rig — noted as a
    dependency already); a `SettingsPage`-level test for the new
    JSON-import-failure notice (no WP-shim support for `$_FILES`/
    `current_user_can`/`wp_verify_nonce` exists yet in this suite, matching
    the pre-existing gap around the CSV-upload path — the validation logic
    itself is fully covered by `GoogleClientConfigImporterTest`).

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
