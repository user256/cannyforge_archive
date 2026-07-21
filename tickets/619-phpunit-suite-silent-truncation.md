# Ticket 619: `composer qa`'s PHPUnit step silently stops mid-suite (most tests never run)

**Sprint:** unassigned
**Status:** Not started
**Owner:** unassigned
**Estimate:** M
**Priority:** P0 — the merge gate is not actually testing most of the codebase

---

## Context

Found while verifying `composer qa` for ticket 609 (unrelated to that
ticket's changes — reproduces identically on a clean `main` checkout with
no edits, confirmed via `git stash`).

Running `vendor/bin/phpunit` (or `composer qa`'s `@test` step) currently
prints the PHPUnit banner, exactly 8 dots (`AdminAssetsTest` plus the first
few `GoogleConnectionControllerTest` methods), and then **stops entirely**:
no more dots, no failure/error report, no `Time:`/`Memory:` summary, no
`OK (n tests)` — yet the process exits `0`. Every test class after that
point (alphabetically: everything past `Admin/GoogleConnectionControllerTest`,
i.e. `Core/*`, `Contracts/*`, `Frontend/*`, `Integration/*`, `Packaging/*`,
and the rest of `Admin/*`) **never runs**, and because the shell exit code
is 0, `composer qa` and any CI step gating on it reports success.

Bisected with `--debug`: the run silently dies partway through
`GoogleConnectionControllerTest` — first observed at
`test_callback_error_with_valid_state_sets_error_status`, then (once that
one test was filtered out) at a *different* method in the same class,
`test_callback_state_cannot_be_replayed`. So it isn't one bad test; something
about this test class/fixture (`GoogleConnectionControllerTest`, ticket
404/614's OAuth callback tests) causes an unreported, silent process exit —
plausibly a real fatal error or an actual `exit()`/similar call that isn't
being converted to a catchable/reportable failure by the WP function shims
(`tests/wp-admin-post-shim.php`), combined with PHPUnit's
`beStrictAboutOutputDuringTests`/`failOnWarning`/`failOnRisky` settings
(`phpunit.xml.dist`) possibly swallowing whatever diagnostic would normally
surface it.

Separately (once `GoogleConnectionControllerTest` is excluded so the rest of
the suite can actually run), exactly one real, pre-existing failure surfaces:
`Admin/SettingsViewTest::test_renders_preview_link` expects
`...rel="noopener noreferrer">Open` in the rendered "Preview Archive" link,
but `SettingsView`'s current markup renders `>Preview Archive <span
class="dashicons...">` instead — the assertion and the markup have drifted
apart (also confirmed pre-existing on clean `main`, unrelated to any current
ticket's diff).

## Goal

`composer qa`'s PHPUnit step actually runs and reports on the full suite
(no silent truncation), and the merge gate fails loudly instead of exiting 0
when that's not the case.

## Acceptance criteria

- [ ] Root-cause the `GoogleConnectionControllerTest` silent exit and fix it
      so the full suite runs and reports normally (`vendor/bin/phpunit` with
      no `--filter` reaches every test class and prints a final summary).
- [ ] Fix (or update) `SettingsViewTest::test_renders_preview_link` to match
      the actual "Preview Archive" link markup, whichever is correct.
- [ ] Add a regression safeguard (e.g. a CI check on PHPUnit's own exit
      status *and* that its final "Tests: N" line matches the expected test
      count) so a future silent truncation like this fails the build instead
      of reporting green.

## Out of scope

- Ticket 609's accessibility work (unrelated; this was discovered while
  verifying it, not caused by it).

## Dependencies

- **Blocks:** none directly, but this undermines confidence in every prior
  "`composer qa` is green" claim for PRs that touched anything alphabetically
  after `Admin/GoogleConnectionControllerTest`.
- **Blocked by:** none
- **Related:** 404, 614 (Google OAuth), 609

## Notes / decisions log

- 2026-07-21 — Filed from ticket 609 verification. Confirmed both issues are
  pre-existing on clean `main` (`git stash` + rerun), not introduced by 609's
  diff. Workaround used to verify 609's own changes: run PHPUnit with
  `--filter '^((?!GoogleConnectionControllerTest).)*$'`, which lets the
  other 265 tests run and shows only the one pre-existing
  `test_renders_preview_link` failure.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
