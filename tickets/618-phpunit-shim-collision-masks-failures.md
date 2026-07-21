# Ticket 618: Fix `wp_safe_redirect` test-shim collision that silently truncates the PHPUnit run

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Discovered while working ticket 605 (SecretCipher hardening) and verifying
`composer qa` on a clean `main` checkout via `git stash` (i.e. this is
pre-existing, unrelated to 605's changes). `tests/bootstrap.php` loads two
shims that both define `wp_safe_redirect()`, guarded by
`if ( ! function_exists( 'wp_safe_redirect' ) )`:

- `tests/wp-hooks-shim.php` (loaded first): a realistic, non-throwing,
  bool-returning stub that records the call via `HookSpy` — matching real
  WordPress semantics (`wp_safe_redirect()` does not exit on its own).
  `ArchivePageTest` relies on exactly this behaviour to test
  `ArchivePage`'s own redirect-then-fallback logic (`src/Frontend/ArchivePage.php`
  calls `if ( wp_safe_redirect(...) ) { ... }`, no unconditional `exit`).
- `tests/wp-admin-post-shim.php` (loaded second): throws
  `WpRedirectException` instead, so callers that do
  `wp_safe_redirect(...); exit;` (the normal WP admin-post pattern) can be
  tested by catching the exception in place of a real `exit`.
  `GoogleConnectionControllerTest` (ticket 614) relies on this.

Because `wp-hooks-shim.php` loads first, its definition wins and
`wp-admin-post-shim.php`'s never gets defined. Any test that reaches
`GoogleConnectionController::redirect_to_settings()` (used by
`handle_callback()`'s error path and `disconnect()`) calls the *real*,
unshimmed `wp_safe_redirect()` (bool-returning, doesn't throw), then falls
through to the actual `exit;` statement in application code — which kills
the entire PHP process running PHPUnit, with exit code 0.

Confirmed reproduction (clean `main`, no ticket-605 changes):
```
php vendor/bin/phpunit --debug
# ...
# Test 'GoogleConnectionControllerTest::test_callback_error_with_valid_state_sets_error_status' started
# (process exits here, code 0, no further output, no test summary)
```

This means:
- **CI's "PHPUnit" step has been reporting green without running the full
  suite.** At minimum 5 tests in `GoogleConnectionControllerTest`
  (`test_callback_error_with_valid_state_sets_error_status`,
  `test_callback_state_cannot_be_replayed`, and three `test_disconnect_*`
  tests) never execute their assertions in a normal full-suite run.
- Because PHPUnit iterates `tests/` and `Admin/` sorts before other
  directories, **no test after this point in the run order ever executes**
  in a full-suite invocation — including, as a concrete example already
  found, `SettingsViewTest::test_renders_preview_link`, which is *itself*
  failing (pre-existing, unrelated to this ticket or to 605) but never gets
  the chance to report that failure in a normal `composer test` run.

## Goal

`composer test` runs the entire suite to completion and its exit code
reflects the true pass/fail state of every test — no test's assertions are
silently skipped by an early process exit.

## Acceptance criteria

- [ ] `php vendor/bin/phpunit --debug` (no `--filter`) runs every test in
      `tests/` to completion and prints a final summary line (`OK` or
      `FAILURES!` with accurate counts) — verified by comparing the printed
      test count against `grep -rc 'public function test_' tests/ | ...`
      (or equivalent) to confirm none were silently dropped.
- [ ] `ArchivePageTest`'s `wp_safe_redirect` bool-return/no-exit assertions
      still pass (the fix must not simply swap which shim wins).
- [ ] `GoogleConnectionControllerTest`'s `wp_safe_redirect`-then-`exit`
      assertions (via `assert_redirects()` / `WpRedirectException`) still
      pass.
- [ ] The now-unmasked pre-existing failure,
      `SettingsViewTest::test_renders_preview_link` (expects a link whose
      text is `Open...` where the current markup renders `Preview Archive`),
      is triaged: either the test is updated to match intentional UI copy,
      or the view is fixed to match intended copy — whichever is correct on
      inspection — so `composer test` is genuinely green end-to-end.
- [ ] A brief comment in `tests/bootstrap.php` (or wherever the fix lives)
      explains why this ordering/scoping matters, so a future shim addition
      doesn't reintroduce the same silent collision.

## Out of scope

- Any broader rework of the test-shim architecture (e.g. ticket 603's "real
  WordPress integration rig") — this ticket only needs the minimal fix so
  `wp_safe_redirect` behaves correctly for both existing use cases.
- Auditing every other shimmed WP function for the same
  `function_exists`-guard collision risk — worth a quick follow-up scan, but
  not required to close this ticket.

## Dependencies

- **Blocks:** none directly, but any ticket relying on "`composer qa` is
  green" as a trust signal is implicitly affected until this lands.
- **Blocked by:** none
- **External:** none

## Approach (optional)

One option: make the "throwing" behaviour namespace-scoped instead of
global. PHP resolves an unqualified function call by first checking the
calling file's own namespace, then falling back to the global namespace.
`GoogleConnectionController` (and any other admin-post-style controller that
does `wp_safe_redirect(...); exit;`) lives in `CannyForge\Archive\Admin`;
defining a `CannyForge\Archive\Admin\wp_safe_redirect()` override (e.g. in a
small file autoloaded only for tests, or required directly by
`wp-admin-post-shim.php`) would let those call sites resolve to the
throwing version while `ArchivePage` (in `CannyForge\Archive\Frontend`)
keeps resolving to the global, non-throwing, `HookSpy`-backed one — no
collision, no shared global mutable behaviour switch needed.

Alternative: keep both in the global namespace but have
`wp-admin-post-shim.php`'s tests use a distinct, explicitly-named function
(not `wp_safe_redirect`) via dependency injection into the controllers,
though that's a larger refactor of the controllers under test.

## Notes / decisions log

- 2026-07-21 — Filed while working ticket 605; reproduction verified via
  `git stash` against a clean `main` (no 605 changes) so this is confirmed
  pre-existing and unrelated to SecretCipher. Verified ticket 605 introduces
  no new regressions by running the suite with the known-crashing test
  methods filtered out via `--filter` (281/281 pass beyond the one
  pre-existing, unrelated `SettingsViewTest` failure this ticket also
  surfaces).

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
