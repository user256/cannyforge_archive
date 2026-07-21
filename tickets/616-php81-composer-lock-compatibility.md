# Ticket 616: Restore PHP 8.1 Composer-lock compatibility

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
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

- [ ] Decide whether PHP 8.1 remains supported; retain it unless an explicit
      compatibility-policy change is approved.
- [ ] Make `composer install` succeed on PHP 8.1, or update the declared
      minimum version, readme, and CI together if support is dropped.
- [ ] Run all QA gates in the claimed minimum-version CI environment.
- [ ] Prevent a newer-local-PHP lock refresh from silently breaking the
      supported floor again.

## Dependencies

- **Blocks:** 699 and release/submission
- **Related:** 607

## Notes / decisions log

- 2026-07-21 — PR #4 Actions runs 29827884219 and 29827912847 failed at
  `composer install` on PHP 8.1.34: the lock contains packages requiring PHP
  >=8.2 and `infection/abstract-testframework-adapter` requiring PHP ^8.3.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
