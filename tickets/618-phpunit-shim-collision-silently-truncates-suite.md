# Ticket 618: `wp_safe_redirect` shim collision silently truncates the whole PHPUnit run

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Discovered while verifying `composer qa` for ticket 601. `tests/wp-hooks-shim.php`
and `tests/wp-admin-post-shim.php` both define `wp_safe_redirect()`, each
guarded by `function_exists( 'wp_safe_redirect' )`. `tests/bootstrap.php`
requires `wp-hooks-shim.php` first, so its definition — record-and-return-bool,
added for ticket 617's "rejected redirect falls through" behaviour in
`ArchivePage` — always wins. `wp-admin-post-shim.php`'s definition (added for
ticket 614's `GoogleConnectionController`) never registers; it throws
`WpRedirectException` to let tests catch a controller method that does
`wp_safe_redirect(...); exit;` and never returns.

Because the winning shim does not throw, any code path shaped like
`wp_safe_redirect(...); exit;` (not wrapped in an `if`) hits a **real, bare
PHP `exit;`** during the test run. `GoogleConnectionController::redirect_to_settings()`
is exactly that shape. The moment a test reaches it, the entire PHPUnit
*process* terminates immediately with exit code 0 — indistinguishable from a
clean pass. `GoogleConnectionControllerTest::test_callback_error_with_valid_state_sets_error_status`
(the 6th test in that file, and — in the default alphabetical run of the whole
suite — around the 8th test overall, right after `AdminAssetsTest`) triggers
this. Every test after it, in every file, silently never runs.

Confirmed reproducible on `main` (not introduced by ticket 601): `git stash`
back to a clean checkout and `vendor/bin/phpunit tests/Admin/GoogleConnectionControllerTest.php`
still stops dead after 5 of 10 tests, and a plain `vendor/bin/phpunit` /
`composer test` run over the whole suite stops after 8 dots — with exit code
`0` both times. `composer qa`'s "baseline is green" is true only in the sense
that the process happens to exit 0; it has not actually executed the bulk of
the suite. This directly masked ticket 619 (a genuine `SettingsViewTest`
failure) since `SettingsViewTest` sorts alphabetically after
`GoogleConnectionControllerTest` and was therefore never reached.

## Goal

A full `composer test` / `vendor/bin/phpunit` run actually executes every test
in `tests/`, and a truncated/aborted run is impossible to mistake for success
(non-zero exit code, or PHPUnit's own summary line).

## Acceptance criteria

- [ ] `vendor/bin/phpunit` (no arguments) runs every test file under `tests/`
      to completion and prints a real summary line (`OK (...)` or `FAILURES!`).
- [ ] The fix does not regress ticket 617's `ArchivePage` "rejected redirect
      falls through" test, which needs `wp_safe_redirect()` to return `false`
      on demand rather than throw.
- [ ] `GoogleConnectionControllerTest`'s full 10 tests run and pass (fixing
      whatever the real shim needs to look like for `redirect_to_settings()`'s
      `wp_safe_redirect(...); exit;` shape to be observable in a test).
- [ ] A regression guard exists so a future shim collision like this fails
      loudly instead of silently — e.g. a smoke test asserting the total
      collected test count is above some floor, or a CI step that greps
      PHPUnit's own end-of-run summary line and fails the build if it's
      missing.

## Out of scope

- Fixing ticket 619's underlying `SettingsView` mismatch (filed separately;
  this ticket only unblocks it from being visible).
- A general redesign of the WP-shim strategy (e.g. moving to Brain\Monkey or
  WP_Mock) — that's a bigger call than this bug warrants.

## Dependencies

- **Blocks:** 619 (needs the suite to actually run `SettingsViewTest` to be
  re-verified after this fix), and honest confidence in every other ticket's
  "`composer qa` is green" claim.
- **Blocked by:** none
- **External:** none

## Approach (optional)

The two call shapes that need different shim behaviour are genuinely
different: `ArchivePage` checks the return value and only falls through when
`false`; `GoogleConnectionController::redirect_to_settings()` assumes success
and calls a bare `exit;` right after. Options:
1. Give `wp-hooks-shim.php`'s `wp_safe_redirect()` a test-controlled "throw
   instead of return" toggle (it already has `cannyforge_test_safe_redirect_result`
   for the return-value case) so `GoogleConnectionControllerTest` can opt in
   per test.
2. Or refactor `redirect_to_settings()` to check the return value like
   `ArchivePage` does, so it's naturally testable without ever reaching a bare
   `exit;` in-process.
Either way, delete the now-redundant, silently-losing definition in
`wp-admin-post-shim.php` rather than leaving two competing shims for the same
function.

## Notes / decisions log

- 2026-07-21 — Found during ticket 601's `composer qa` verification. Confirmed
  pre-existing on `main` via `git stash`; not caused by ticket 601's changes.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
