# Ticket 719: Align the plugin-header and readme license declarations

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** S
**Priority:** P3 — submission metadata consistency

---

## Context

The main plugin header declares `GPL-2.0-or-later`; `readme.txt` declares
`GPLv2 or later`. They are intended to mean the same thing and current tools
accept both, but a submission should not make a reviewer reconcile two textual
license declarations.

## Goal

Every distributed plugin metadata surface declares the same GPL-compatible
license using one agreed wording and URI.

## Acceptance criteria

- [ ] The plugin header and `readme.txt` use identical license wording and
      compatible license URI values.
- [ ] Package metadata and any release documentation do not state a conflicting
      license.
- [ ] `composer qa`, the readme validator, and WordPress Plugin Check pass.

## Out of scope

- Changing the project license or adding a dual-licensing model.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit as low-cost metadata
  hygiene.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. No unrelated licensing change is bundled into this ticket.
