# Ticket 616: Restore PHP 8.1 Composer-lock compatibility

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** background-agent
**Estimate:** S
**Priority:** P0 — release blocker

---

## Context

Ticket 611 refreshed `composer.lock` from PHP 8.3. CI runs `composer install`
on PHP 8.1, matching the plugin's declared `>=8.1` requirement, but now fails
before QA because locked development packages require PHP 8.2 or 8.3.

## Goal

Make the lock file, declared support policy, documentation, and CI agree on a
verified PHP version.

## Acceptance criteria

- [x] Decide whether PHP 8.1 remains supported; retain it unless an explicit
      compatibility-policy change is approved.
- [x] Make `composer install` succeed on PHP 8.1, or update the declared
      minimum version, readme, and CI together if support is dropped.
- [x] Run all QA gates in the claimed minimum-version CI environment.
- [x] Prevent a newer-local-PHP lock refresh from silently breaking the
      supported floor again.

## Dependencies

- **Blocks:** 699 and release/submission
- **Related:** 607

## Notes / decisions log

- 2026-07-21 — PR #4 Actions runs 29827884219 and 29827912847 failed at
  `composer install` on PHP 8.1.34: the lock contains packages requiring PHP
  >=8.2 and `infection/abstract-testframework-adapter` requiring PHP ^8.3.
- 2026-07-21 — PHP 8.1 remains the supported floor. The development
  constraint now uses Infection 0.28, whose adapter and transitive Symfony
  packages resolve on PHP 8.1, and `config.platform.php` pins lock refreshes
  to that floor even when run locally on a newer PHP version. `composer
  validate`, a dry-run dependency update, and install from the regenerated
  lock all pass locally. The full QA gates were run on PHP 8.3.6; the
  workflow's PHP 8.1 run remains the final minimum-runtime verification.
- 2026-07-21 — GitHub Actions run 29830681989 passed the PHP 8.1 quality
  gates and the Node 20 Jest gate on PR #6.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
