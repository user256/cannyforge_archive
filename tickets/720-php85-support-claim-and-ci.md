# Ticket 720: Make the PHP compatibility claim match supported runtimes and CI

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** M
**Priority:** P2 — release-claim accuracy

---

## Context

The readme says CI tests every supported PHP version from 8.1 through 8.4.
The plugin's Composer constraint permits newer PHP versions, while PHP 8.5 is
now a stable release. The current statement can become stale and overstates
the verification evidence available to a WordPress.org reviewer.

## Goal

The declared PHP support policy, CI matrix, Composer constraint, and public
compatibility claim are mutually accurate.

## Acceptance criteria

- [ ] Decide and record whether PHP 8.5 is supported for this release.
- [ ] If supported, add PHP 8.5 to the CI matrix and make the full quality gate
      pass there; if not, constrain support or amend the readme so it does not
      imply untested support.
- [ ] The `Requires PHP` value remains an accurate minimum, and the readme does
      not claim continuous verification beyond the actual CI matrix.
- [ ] The decision and matrix evidence are recorded in the release/readiness
      documentation.

## Out of scope

- Raising the PHP 8.1 minimum without a separate compatibility decision.
- PHPUnit/PHPStan major-version modernisation (ticket 701).

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** CI runner availability for PHP 8.5

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit after the public runtime
  claim was compared with the current supported PHP release line.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any toolchain incompatibility found is filed separately.
