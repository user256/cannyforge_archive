# Ticket 619: `composer test` silently truncates the suite after ~13% of test files (masks a real failure)

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Discovered while validating ticket 607 (CI matrix + repo hygiene): `composer
test` / `composer qa` currently exit `0` ("green") without ever running most
of the suite. This is a false green, not a real pass, and it predates ticket
607 — nothing in 607's changes (qa.yml, readme.txt) touches the affected
files.

**Root cause:** `wp_safe_redirect()` is defined twice, guarded by
`function_exists()` in both places:

- `tests/wp-hooks-shim.php:221` — the original, general-purpose shim. It
  *records* the call via `HookSpy` and returns a `bool`; its own docblock
  says "Callers still must `exit` themselves afterwards, exactly as in
  production — this shim does not."
- `tests/wp-admin-post-shim.php:146` — added later (per that file's
  docblock, for ticket 614) specifically so `GoogleConnectionControllerTest`
  can catch a `WpRedirectException` instead of the process actually exiting.

`tests/bootstrap.php` requires `wp-hooks-shim.php` *before*
`wp-admin-post-shim.php`, so the first (non-throwing) definition wins and the
second is silently skipped by its own `function_exists()` guard. Any test
that exercises a `GoogleConnectionController` code path ending in
`wp_safe_redirect(...); exit;` (e.g. `handle_callback()`'s error branch)
now hits the **real** PHP `exit;` statement — because the shim returned
normally instead of throwing — which terminates the entire PHPUnit process
mid-run, with exit code `0` (a bare `exit;` is a clean, zero-status exit).

Since PHPUnit walks `tests/` in directory order and `Admin/` sorts before
`Bootstrap/`, `Contracts/`, `Core/`, `Frontend/`, `Integration/`, and
`Packaging/`, a full `vendor/bin/phpunit` run currently dies partway through
`tests/Admin/GoogleConnectionControllerTest.php` (test 6 of 10) and never
executes the other ~44 of 50 test files — roughly 240+ tests — while still
reporting `OK`/exit `0` to the shell. `composer qa`'s test step, and every CI
run to date, has been green for this reason, not because the suite passed.

**A real, currently-masked failure was found once this was worked around:**
`tests/Admin/SettingsViewTest.php::test_renders_preview_link` (and likely
`test_omits_preview_link_without_preview_url`, not yet checked) asserts link
text `>Open` and iframe `title="Preview"`, but the current
`Admin/SettingsView` output renders `>Preview Archive ...` and
`title="Archive preview"`. This looks like a leftover from ticket 613
("make admin settings UI truthful, complete, and accessible", just merged)
changing the wording without updating this assertion. This has been sitting
un-caught in the suite because it never actually gets to run.

## Goal

`composer test` runs and reports on the entire suite every time, with no
silent early termination, and any real failures it surfaces (starting with
`SettingsViewTest::test_renders_preview_link`) are fixed or explicitly
triaged.

## Acceptance criteria

- [ ] The duplicate `wp_safe_redirect()` definitions are consolidated into
      one shim. Given `GoogleConnectionControllerTest` needs the throwing
      behaviour and was written against it, the throwing version should win
      — either by removing the non-throwing definition from
      `wp-hooks-shim.php` (auditing/fixing whatever older tests relied on
      `HookSpy::record('wp_safe_redirect', ...)`) or by requiring
      `wp-admin-post-shim.php` before `wp-hooks-shim.php` in
      `tests/bootstrap.php` and confirming that ordering doesn't break
      anything else. This likely belongs alongside ticket 602's "dedicated
      Google admin-controller test harness" work — coordinate rather than
      duplicating.
- [ ] A full `vendor/bin/phpunit` run (no path arguments) executes all 50+
      test files / 240+ tests and prints a real final summary line
      (`OK (...)` or `FAILURES!`), not a truncated dot count.
- [ ] `SettingsViewTest::test_renders_preview_link` (and any sibling test in
      that file affected by the same wording change) is reconciled with the
      actual `Admin/SettingsView` output — fix the view or the test,
      whichever is the truthful/intended copy, referencing ticket 613.
- [ ] `composer qa` is re-run in full afterward and its real (not
      early-truncated) result is recorded in this ticket's decisions log.
- [ ] Add a CI-visible guard against this class of bug recurring silently:
      at minimum, assert in the test run itself (or a CI step) that the
      number of executed tests is above some sane floor, so a future
      premature `exit` inside a test run fails loudly instead of reporting
      green.

## Out of scope

- PHPUnit 10/11 / PHPStan 2.x upgrades — ticket 618.
- Any other latent failures the now-unblocked back half of the suite might
  reveal beyond `SettingsViewTest` — triage those as they surface; file
  separately if unrelated to the shim collision.

## Dependencies

- **Blocks:** none directly, but every ticket that has relied on "`composer
  qa` is green" as a merge gate (including 607, 613, 614, 616, 617) should
  be treated as **unverified** for anything past
  `tests/Admin/GoogleConnectionControllerTest.php`'s sixth test until this
  lands.
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — Found while confirming ticket 607's `composer qa` quality
  gate. Reproduced deterministically with a standalone PHP script (bypassing
  PHPUnit entirely) that calls `GoogleConnectionController::handle_callback()`
  with an `error` query param and a valid CSRF state: the process exits
  silently at the `exit;` in `redirect_to_settings()` because
  `wp_safe_redirect()` resolved to the non-throwing shim in
  `wp-hooks-shim.php:221`, confirmed via `ReflectionFunction`. Running each
  `tests/` subdirectory individually (working around the collision) showed
  `Bootstrap` (3), `Contracts` (10), `Core` (131), `Frontend` (41),
  `Integration` (49), and `Packaging` (3) all genuinely pass — 237 tests,
  all green. Only `Admin/` is affected, and within it, only
  `SettingsViewTest::test_renders_preview_link` fails once actually reached.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
