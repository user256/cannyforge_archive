# Ticket 618: `wp_safe_redirect` shim collision silently truncated the whole PHPUnit run

**Sprint:** 6 — Trust & Scale
**Status:** Resolved
**Owner:** unassigned
**Estimate:** M

---

## Context

Independently discovered by five parallel Sprint 6 tickets (601, 602, 605,
607, 609, 610) while each verified `composer qa` in isolation.
`tests/wp-hooks-shim.php` and `tests/wp-admin-post-shim.php` both defined
`wp_safe_redirect()`, each guarded by `function_exists( 'wp_safe_redirect' )`.
`tests/bootstrap.php` required `wp-hooks-shim.php` first, so its
definition — record-and-return-bool, added for ticket 617's "rejected
redirect falls through" behaviour in `ArchivePage` — always won.
`wp-admin-post-shim.php`'s definition (added for ticket 614's
`GoogleConnectionController`) never registered; it was meant to throw
`WpRedirectException` so a test could catch a controller method that does
`wp_safe_redirect(...); exit;` and never returns.

Because the winning shim did not throw, any code path shaped like
`wp_safe_redirect(...); exit;` (not wrapped in an `if`) hit a **real, bare
PHP `exit;`** during the test run. `GoogleConnectionController`,
`Ga4RefreshController`, and `SearchConsoleRefreshController` (all in
`CannyForge\Archive\Admin`) are exactly that shape. The moment a test reached
one of them, the entire PHPUnit *process* terminated immediately with exit
code 0 — indistinguishable from a clean pass. Roughly the 8th test in the
default alphabetical run triggered this; every test after it, in every file,
silently never ran. `composer qa`'s "green" baseline claimed throughout
Sprint 6 (and likely earlier sprints) was true only in the sense that the
process happened to exit 0 — it had not actually executed the bulk of the
suite. This also masked a real, unrelated failure in
`SettingsViewTest::test_renders_preview_link` (asserting stale button/iframe
copy from before ticket 613's UI redesign), since that test file sorts
alphabetically after the collision point.

## Resolution

Both `ArchivePage::redirect_tail()` (needs `wp_safe_redirect()` to return a
real bool so it can decide whether to try a fallback URL) and the three
Admin controllers (assume success, unconditionally `exit`, so testing them
needs `wp_safe_redirect()` to throw before that `exit` is ever reached) are
legitimate, incompatible needs for the same global function name. Fixed via
PHP's namespace function-resolution fallback rather than favoring one caller
over the other:

- `tests/wp-hooks-shim.php` keeps the single global `wp_safe_redirect()`,
  restored to its original real-WordPress-contract bool-return behaviour —
  this is what `Frontend\ArchivePage` resolves to.
- A new `tests/wp-admin-redirect-shim.php` defines `wp_safe_redirect()`
  *inside* `namespace CannyForge\Archive\Admin`, throwing
  `WpRedirectException`. PHP resolves an unqualified call in that namespace
  to this local override first, so only the Admin controllers get the
  throwing behaviour; everything else still falls back to the global,
  bool-returning shim.
- The redundant, always-losing definition in `wp-admin-post-shim.php` was
  removed.
- `SettingsViewTest::test_renders_preview_link`'s stale assertions (expected
  `>Open` / `title="Preview"`) were updated to the current
  `>Preview Archive` / `title="Archive preview"` markup.

Fixed directly on `main` (commit `c41915a`) as foundational infrastructure
before any Sprint 6 branch could be trusted to merge, since every other
ticket's "`composer qa` is green" claim depended on it. Full suite now runs
to completion (275 tests at the time of the fix, growing as each ticket
merged) with a real `OK (...)` / `FAILURES!` summary every time.

## Acceptance criteria

- [x] `vendor/bin/phpunit` (no arguments) runs every test file under `tests/`
      to completion and prints a real summary line.
- [x] The fix does not regress ticket 617's `ArchivePage` "rejected redirect
      falls through" test — `wp_safe_redirect()` still returns `false` on
      demand via the `cannyforge_test_safe_redirect_result` global.
- [x] `GoogleConnectionControllerTest` (and the Ga4/SearchConsole refresh
      controller tests added by ticket 602) run in full and pass.
- [ ] A regression guard against a future silent shim collision (e.g. a CI
      step asserting a minimum collected-test-count floor) was not added —
      left as a smaller optional follow-up if it recurs; the namespace-scoped
      fix removes the specific collision that caused this one.

## Dependencies

- **Blocked:** every Sprint 6 ticket's "qa green" claim, retroactively
  unblocked once this landed.
- **Blocked by:** none

## Notes / decisions log

- 2026-07-21 — Independently found by tickets 601, 602, 605, 607, 609, and
  610, each filing a near-duplicate ticket (originally numbered 618/619
  across six different files with six different slugs, since each agent
  worked from the same pre-fix ticket-number snapshot in an isolated
  worktree). Consolidated into this single record during integration; the
  duplicate files are deleted rather than kept, since the underlying bug is
  the same one bug regardless of which ticket noticed it first.
- 2026-07-21 — Fixed directly on `main`, commit `c41915a`.

---

## Definition of done

1. All acceptance criteria above are checked (except the optional regression
   guard, explicitly left as a follow-up rather than false-checked).
2. Changes are merged to the main branch. Done.
3. Not part of the original `tickets/overview.md` Sprint 6 table (discovered
   mid-sprint); recorded here for the audit trail instead.
4. No further follow-up filed — the specific collision is fixed at the root.
