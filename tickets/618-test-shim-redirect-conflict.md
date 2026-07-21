# Ticket 618: duplicate `wp_safe_redirect()` test shim silently kills the PHPUnit run mid-suite

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Found while verifying `composer qa` for ticket 606. Two files both define
`wp_safe_redirect()`, each guarded with `if ( ! function_exists( 'wp_safe_redirect' ) )`:

- `tests/wp-hooks-shim.php` — records the call and **returns a bool** (used
  by `tests/Frontend/ArchivePageTest.php`, which asserts on the recorded
  call/return value and does not expect an exception).
- `tests/wp-admin-post-shim.php` — **throws `WpRedirectException`** instead
  of returning (used by `tests/Admin/GoogleConnectionControllerTest.php`,
  which catches that exception to assert a redirect happened).

`tests/bootstrap.php` requires `wp-hooks-shim.php` before
`wp-admin-post-shim.php`, so the non-throwing definition always wins — the
second file's `function_exists()` guard sees it already defined and skips
its own version. Every `GoogleConnectionController` code path that ends in
`redirect_to_settings()` (which calls `wp_safe_redirect(...); exit;`) then
falls through the non-throwing shim straight into a **real, literal `exit;`**
in production code, terminating the entire PHP process running PHPUnit.

Reproduced on a clean checkout (no ticket 606 changes applied):

```sh
vendor/bin/phpunit --filter test_callback_error_with_valid_state_sets_error_status tests/Admin/GoogleConnectionControllerTest.php
```

completes in ~0.3s printing only the PHPUnit banner — no dots, no OK/FAILURES
summary, exit code 0. Running the full suite (`composer test`) dies the same
way the moment it reaches any `GoogleConnectionControllerTest` method that
exercises a redirect: it prints a handful of dots for whatever ran first,
then the whole process silently disappears with exit code 0. That exit code
is the dangerous part — a CI step checking only the exit code would read
this as a pass despite the suite never finishing and the rest of the tests
never running.

This is pre-existing on `main` as of this writing (likely introduced or
exposed around ticket 614, when `GoogleConnectionControllerTest.php` and its
throwing-redirect assumption were added); it is not caused by ticket 606's
changes and out of that ticket's scope to fix.

## Goal

`composer test` runs to completion and reports an accurate pass/fail/error
count for every test in the suite, including every `GoogleConnectionControllerTest`
method that exercises a redirect.

## Acceptance criteria

- [ ] `vendor/bin/phpunit` (full suite, no `--filter`) prints a final
      `OK` / `FAILURES!` summary line and exits with a status code that
      reflects the actual result — it never silently disappears mid-run.
- [ ] `tests/Frontend/ArchivePageTest.php` and
      `tests/Admin/GoogleConnectionControllerTest.php` both keep passing
      with their existing assertions (one needs the recording/bool-return
      behaviour, the other needs the throwing behaviour) — reconcile this
      without one file "winning" by load-order accident. Options include:
      giving each shim's `wp_safe_redirect` a distinct trigger (e.g. only
      throw when a `WpRedirectException`-catching test opts in via a
      global flag), or consolidating both shims' redirect functions into
      one file with one, deliberately-chosen behaviour.
- [ ] A regression test (or CI check) that would have caught this — e.g.
      asserting the full suite's test count matches the number of test
      *methods* discovered in `tests/`, so a silent early process death
      shows up as a count mismatch rather than a quiet green exit code.

## Out of scope

- Any other test-shim duplication that isn't causing a silent process exit
  today — audit if you like, but don't fix speculatively.

## Dependencies

- **Blocks:** none directly, but every ticket that claims "`composer qa`
  is green" is only as trustworthy as this gets fixed — full-suite runs
  currently can't be verified end-to-end.
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — Found while verifying ticket 606's `composer qa` gate.
  Confirmed present on a clean checkout via `git stash -u` before any
  ticket 606 changes, so this is pre-existing and unrelated to uninstall
  cleanup work.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
