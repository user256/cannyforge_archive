# Ticket 713: Remove leftover Property-step “Save property and continue” CTA

**Sprint:** 6 — Trust & Scale
**Status:** Completed
**Owner:** unassigned
**Estimate:** S
**Priority:** P2 — confusing / leftover modal UI

---

## Context

`GoogleWizardAccountStepsView::property()` embeds `GooglePropertySelectorView`,
which still renders a primary **Save property and continue** button (modal-era
copy) **above** the GA4 selector and report-window fields. The wizard already
has **Save and finish** in the footer. Mid-form submit encourages finishing
before GA4 is considered, duplicates CTAs, and reuses settings-modal language
(“continue”) on a finish step.

## Goal

The Property step has a single save CTA in the wizard footer; the shared
selector only renders the property field + Load action when used from the
wizard (or gains a flag to suppress the old primary button).

## Acceptance criteria

- [x] Wizard Property step shows one primary save control (“Save and finish” or
      equivalent), placed after all fields including GA4 + report window.
- [x] “Load properties” / “Load GA4 properties” remain available.
- [x] Settings/main-page callers of `GooglePropertySelectorView` (if any remain)
      still have a usable save path, or are confirmed unused after the wizard
      migration.
- [x] Tests updated: no “Save property and continue” in wizard Property HTML;
      Load action URL still present.

## Out of scope

- Redesigning the GA4 selector control itself.
- Changing overlay save semantics.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md).
- 2026-07-22 — Removed the obsolete modal-era CTA while retaining property-load actions and added render coverage; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 713 marked done when completed.
4. Follow-ups filed, not absorbed.
