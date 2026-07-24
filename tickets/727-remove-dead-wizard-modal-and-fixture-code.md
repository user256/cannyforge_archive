# Ticket 727: Remove dead Google wizard modal and fixture code from the distributable

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Complete
**Owner:** unassigned
**Estimate:** S
**Priority:** P1 — WordPress.org package hygiene / reviewer confusion

---

## Context

The live Google setup path is the full-page `GoogleWizardPage` / `GoogleWizardView`
flow. These classes are still in `src/` and therefore ship in the runtime ZIP via
the first-party autoloader tree, but nothing constructs them:

- `GoogleWizardModalView` (~409 lines) — superseded modal wizard
- `GoogleWizardProgressView` (~60 lines) — only referenced by the modal
- `FixtureEntryProvider` (~54 lines) — Sprint 1 placeholder, unused by `Plugin`

Reviewers read the whole tree. Dead admin UI that still mentions Connect / Save
Google details looks like unfinished dual surfaces, and unused files violate the
“don’t ship unnecessary code” hygiene called out in earlier packaging tickets
(501 / 722). The modal strings also still inflate `languages/cannyforge-archive.pot`.

## Goal

The distributable contains only Google-wizard and archive-provider code that the
runtime composition root can actually reach.

## Acceptance criteria

- [x] `GoogleWizardModalView` and `GoogleWizardProgressView` are removed (or
      proven reachable from a live admin path and covered by tests).
- [x] `FixtureEntryProvider` is removed or moved out of the shipping tree.
- [x] `languages/cannyforge-archive.pot` is regenerated without the dead modal
      strings (or the regeneration step is documented and run before release).
- [x] Packaging / unit suite still passes; no admin entry point regresses to a
      missing class.
- [x] Grep of the built ZIP shows no `GoogleWizardModalView` / `FixtureEntryProvider`
      symbols.

## Out of scope

- Redesigning the full-page wizard.
- Removing other unused private helpers that are still called from tests only
  (keep test doubles in `tests/`).

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-24 — Deleted modal/progress views; moved FixtureEntryProvider into tests/; regenerated POT.

- 2026-07-24 — Filed from [plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md).
  Confirmed no PHP/JS references to the modal outside its own file and the
  progress view it instantiates.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox marked done.
4. Follow-ups filed, not absorbed.
