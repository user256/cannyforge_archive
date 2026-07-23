# Ticket 712: Keep `cf_signal` on wizard Back / checklist links

**Sprint:** 6 — Trust & Scale
**Status:** Completed
**Owner:** unassigned
**Estimate:** S
**Priority:** P1 — wizard state loss

---

## Context

`GoogleWizardSetupStepsView` correctly threads `cf_signal` on Continue/Back.
`GoogleWizardAccountStepsView::back_link()` and most checklist “Finish this
step” links call `GoogleWizardPage::url( $step )` **without** the signal.
Stepping Back from Connect/Property (or using checklist links) drops
`sc_ga4`, so consent copy, App API instructions, and the GA4 picker can
silently fall back to Search Console-only mid-flow.

## Goal

Every in-wizard navigation that should preserve the chosen signal does so.

## Acceptance criteria

- [x] Account-step Back links include the current `cf_signal`.
- [x] Checklist “Finish this step” links preserve the current signal (or derive
      it the same way as `GoogleWizardPage::signal()`).
- [x] Stepper links continue to carry the signal (already true — keep covered).
- [x] View/page tests assert Back from Connect with `sc_ga4` still shows
      Analytics in the consent copy after returning to Connect.

## Out of scope

- Changing step gating / reachability rules (ticket 711).
- Query-arg redesign.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md).
- 2026-07-22 — Threaded `cf_signal` through account Back, checklist, and refresh links and added navigation coverage; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 712 marked done when completed.
4. Follow-ups filed, not absorbed.
