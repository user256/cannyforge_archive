# Ticket 618: Guard the test harness against a silently-truncated PHPUnit run

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

While implementing ticket 602, a pre-existing bug in the hand-rolled WP shims
was found: `wp_safe_redirect()` was declared in both `tests/wp-hooks-shim.php`
and `tests/wp-admin-post-shim.php`, each guarded by `function_exists()`.
Because `wp-hooks-shim.php` loads first (`tests/bootstrap.php`), its
non-throwing, bool-returning definition always won, and
`wp-admin-post-shim.php`'s throwing definition (needed so
`wp_safe_redirect(...); exit;` call sites don't kill the test process) was
dead code. In practice this meant: any admin-post controller test that hit a
successful `wp_safe_redirect()` fell through into a real, literal `exit;`
statement, silently terminating the whole PHP process with **exit code 0**
and no PHPUnit summary — before that test, and everything scheduled after it,
ever ran. `composer qa`/CI read the 0 exit code as success the entire time.

Concretely, before the ticket 602 fix, `vendor/bin/phpunit` on `main` always
stopped dead after exactly 8 dots (partway through
`GoogleConnectionControllerTest`), silently omitting roughly 267 of the ~275
tests that existed, yet still reporting a clean exit. This is exactly the
kind of failure a size/style/architecture gate (PHPCS, PHPStan, PHPArkitect,
PHPMD) cannot catch, because none of them execute the suite — only PHPUnit's
own exit code does, and that's precisely what was silently lying.

## Goal

A truncated/aborted PHPUnit run — for any reason, not just this specific
shim bug — is loud and fails CI, instead of silently reporting success.

## Acceptance criteria

- [ ] A repo script or CI step verifies the PHPUnit run actually reached its
      final summary line (e.g. parses for `OK (`/`FAILURES!`/`ERRORS!` in the
      captured output, or uses a JUnit/teamcity logger and checks the reported
      test count against an expected minimum) and fails loudly if the process
      exited without one.
- [ ] A regression test (or a documented manual check) exists for the root
      cause found in ticket 602: two shims silently racing to define the same
      WordPress function via `function_exists()` guards, with the loser's
      intended behaviour (throw-to-simulate-exit vs record-and-continue)
      silently discarded. At minimum, document the hazard prominently at the
      top of `tests/bootstrap.php` so a future shim addition checks for
      existing definitions first.
- [ ] `composer qa`/`composer test` fails (non-zero) if the PHPUnit test
      count drops unexpectedly between runs without an explicit,
      intentional removal — a floor-count assertion is enough; it does not
      need to be exact.

## Out of scope

- Migrating the test suite to real WordPress (ticket 603 covers that
  separately).
- Auditing every existing shim function for other latent collisions beyond
  the `wp_safe_redirect()` one already fixed in ticket 602 — that fix is
  done; this ticket is about making the *next* one loud instead of silent.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — Filed from ticket 602 after the `wp_safe_redirect()` shim
  collision (see that ticket's decisions log for the full root-cause
  writeup) was found to have been silently truncating full test-suite runs,
  with `composer qa` reporting a green exit code throughout.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
