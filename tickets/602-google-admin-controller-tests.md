# Ticket 602: Unit tests for the Google admin controllers and settings surface

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** unassigned
**Estimate:** L

---

## Context

The untested classes cluster exactly where the risk is:
`Admin/GoogleConnectionController` (357 lines — OAuth connect/disconnect,
CSRF state transient, capability checks), `Admin/Ga4RefreshController`,
`Admin/SearchConsoleRefreshController`, `Admin/SettingsPage` (340 lines),
and the two largest files in the codebase — `Admin/ModeSettingsPanelView`
(534 lines) and `Admin/SettingsView` (480 lines) — which have partial or no
coverage. Sprint 4's review gate shipped with "live Google smoke deferred",
so right now *nothing* — neither tests nor a live check — verifies the OAuth
flow end to end.

## Goal

The OAuth controller's security decisions and the settings surface's
render/parse round-trip are pinned by unit tests.

## Acceptance criteria

- [x] `tests/Admin/GoogleConnectionControllerTest.php` covers: connect refused
      without capability; connect refused without valid nonce; state transient
      created on connect and consumed exactly once on callback; callback with
      missing/expired/foreign state rejected; disconnect clears the token
      store; user-facing notices produced on each failure path.
      (Landed as three focused classes —
      `GoogleConnectionControllerConnectTest`,
      `GoogleConnectionControllerCallbackTest`,
      `GoogleConnectionControllerDisconnectTest` — sharing fixture/helpers via
      an abstract `GoogleConnectionControllerTestCase`, rather than one
      `GoogleConnectionControllerTest.php`; see decisions log.)
- [x] `tests/Admin/Ga4RefreshControllerTest.php` and
      `tests/Admin/SearchConsoleRefreshControllerTest.php` cover the
      capability/nonce gate and the success/failure notice paths, with the
      refresher doubled.
- [x] `tests/Admin/SettingsPageTest.php` covers menu registration, capability
      gating, and the save round-trip (posted form → parser → repository).
- [x] Snapshot-style render tests exist for `ModeSettingsPanelView` and the
      untested regions of `SettingsView`: current settings values appear in
      the output, all output is escaped (assert a value containing
      `<script>` renders entity-encoded).
- [x] `composer qa` remains green; PHPMD budgets are not relaxed to make the
      views testable — if a view is too big to test, extract, don't exempt.

## Out of scope

- Live OAuth verification against real Google credentials (ticket 603 covers
  the harness; the live run stays a manual gate item in 699).
- Refactoring the views beyond what testability requires.

## Dependencies

- **Blocks:** 604
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — **Found and fixed a critical, pre-existing test-harness bug
  that was silently truncating every full `composer qa`/`vendor/bin/phpunit`
  run.** `tests/wp-hooks-shim.php` and `tests/wp-admin-post-shim.php` both
  declared `wp_safe_redirect()`, each guarded by `function_exists()`.
  `wp-hooks-shim.php` loads first in `tests/bootstrap.php`, so its
  non-throwing, bool-returning definition (used by `ArchivePageTest`, which
  inspects the return value) always won; `wp-admin-post-shim.php`'s
  throwing definition (needed by `GoogleConnectionController`'s
  `wp_safe_redirect(...); exit;` call sites) silently never installed. In the
  test runtime this meant any call to `wp_safe_redirect()` that would
  succeed in production fell through to the real, literal `exit;` statement
  immediately after it — killing the whole PHP process with **exit code 0**
  and no PHPUnit summary, before that test (and everything after it) ever
  ran. Because the process exit code was 0, this looked like a green build to
  `composer qa`/CI the entire time. Confirmed empirically: before the fix,
  `vendor/bin/phpunit` always stopped dead after exactly 8 dots (partway
  through `GoogleConnectionControllerTest`, the first suite to exercise a
  successful `wp_safe_redirect()` call), silently omitting ~267 of the ~275
  tests that existed on `main`, yet still reporting success.
  Fixed by making `wp_safe_redirect()` (single canonical definition, now only
  in `wp-hooks-shim.php`) throw `WpRedirectException` when the redirect would
  succeed — matching every call site in this codebase, which always follows
  a truthy result with `exit` — and return `false` (no throw) when rejected,
  preserving `ArchivePageTest`'s existing rejected-redirect assertions. The
  now-dead-code `wp_safe_redirect()` block in `wp-admin-post-shim.php` was
  removed and replaced with a comment pointing at the canonical definition.
  Filed ticket 618 to harden the harness against this class of bug recurring
  silently.
- 2026-07-21 — While re-verifying the (previously never actually reached)
  full suite, also found `SettingsViewTest::test_renders_preview_link` was
  failing on `main` — masked by the same bug. Ticket 613's decision log
  claims this was fixed, but only the view's copy was updated (to "Preview
  Archive" / iframe `title="Archive preview"`); the test assertions were
  left expecting the older "Open" text and `title="Preview"`. Updated the
  test to match the current, intentional (613) copy rather than reverting
  the view.
- 2026-07-21 — `check_admin_referer()` and (new) `wp_verify_nonce()` in
  `tests/wp-admin-post-shim.php` now support simulating an invalid nonce via
  `$GLOBALS['cannyforge_test_admin_referer_valid']`/matching against the
  `test-nonce-{$action}` convention `wp_create_nonce()`/`wp_nonce_field()`
  already use, so "refused without a valid nonce" is actually exercisable —
  previously `check_admin_referer()` unconditionally returned `true`.
- 2026-07-21 — `ModeSettingsPanelView` (295 lines) and `SettingsView` (415
  lines) were already under the PHPMD budget and straightforward to test via
  output buffering — ticket 611 had already split `GoogleWizardModalView`
  out of `ModeSettingsPanelView` for an earlier ticket, ahead of this one, so
  no further *production* refactor was needed for testability. No src/
  changes were made to either view.
- 2026-07-21 — PHPMD's `ExcessiveClassLength` rule (phpmd.xml, budget 400)
  runs over `src,tests` (see `composer.json`'s `mess` script), so it gates
  test classes too. Adding the ticket 602 capability/nonce/notice/state-
  lifecycle tests pushed a single `GoogleConnectionControllerTest.php` to
  561 logical lines. Per this ticket's own rule ("if too big to test,
  extract, don't exempt"), applied the same principle to the test file: split
  into `GoogleConnectionControllerConnectTest`,
  `GoogleConnectionControllerCallbackTest`, and
  `GoogleConnectionControllerDisconnectTest`, each under budget, sharing
  fixture/helpers via an abstract `GoogleConnectionControllerTestCase` base
  (PHPUnit skips abstract classes during test discovery, so it never runs as
  a "test class" itself).
- 2026-07-21 — Final `composer qa`: green. 306 tests, 853 assertions (up
  from an actually-verified 275/775 baseline after the harness fix above;
  the previously-claimed "~200 tests" baseline was never really exercised in
  full, per the first finding).

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
