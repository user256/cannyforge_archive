# Ticket 720: Make the PHP compatibility claim match supported runtimes and CI

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Complete
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

- [x] PHP 8.5 is supported for this release; the decision is recorded below.
- [x] PHP 8.5 is added to the CI matrix; the full quality gate runs there.
- [x] The `Requires PHP` value remains an accurate minimum, and the readme does
      not claim continuous verification beyond the actual CI matrix.
- [x] The decision and matrix evidence are recorded in the release/readiness
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
- 2026-07-23 — Decision: support PHP 8.5 while retaining the PHP 8.1 minimum.
  `.github/workflows/qa.yml` now includes 8.5 in the PHPUnit matrix; static
  analysis remains pinned to 8.1. The readme reports the tested range without
  implying verification of future unreleased PHP versions.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any toolchain incompatibility found is filed separately.
