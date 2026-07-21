# Ticket 611: Restore the release gate and package only runtime files

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** M
**Priority:** P0 — release blocker

---

## Context

The 2026-07-21 audit ran every quality gate independently against the current
working tree. `composer qa` stops at 42 PHPCS errors; independent runs also
found 8 PHPStan errors, one Rector diff, 6 PHPMD violations, one PHPUnit
failure, and an out-of-date Composer lock content hash. `git diff --check`
finds trailing whitespace. The built ZIP also contains the development-only
`rebuild_ui.py`, regressing ticket 501's runtime-only packaging guarantee.

## Goal

The current UI branch is mergeable and produces a reproducible, runtime-only
WordPress plugin ZIP.

## Acceptance criteria

- [ ] `composer qa` passes from a clean checkout on PHP 8.1; PHPMD thresholds
      are not weakened and failing view/model classes are split where needed.
- [ ] `composer validate --strict --no-check-publish` passes and
      `composer.lock` matches `composer.json`.
- [ ] The stale `SettingsViewTest` assertion is updated to the intentional
      preview contract, and the revised behavior is asserted rather than simply
      deleting the failing expectation.
- [ ] `git diff --check` produces no output.
- [ ] `composer dist` excludes `rebuild_ui.py` and every other development
      helper; the script is removed if it is no longer needed or explicitly
      excluded from the distribution.
- [ ] A packaging assertion compares the staged ZIP against an allowed runtime
      file/pattern list and fails if Python scripts, tests, caches, environment
      files, ticket files, or tool configuration leak into a future ZIP.
- [ ] Every PHP file in `dist/cannyforge-archive/` passes `php -l`; the ZIP can
      be installed and activated on a disposable WordPress instance.

## Out of scope

- Functional redesign of the new settings UI (ticket 613).
- Broad CI-version expansion (ticket 607).
- New product features.

## Dependencies

- **Blocks:** 699 and any release/submission from the current working tree
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — Audit baseline: PHPCS 42; PHPStan 8; Rector 1; PHPMD 6;
  PHPUnit 1; Composer validation 1; `rebuild_ui.py` present in version 0.1.1 ZIP.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
