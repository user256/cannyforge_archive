# Ticket 604: Set the Infection minMsi and make mutation testing a merge gate

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

`.github/workflows/qa.yml` runs Infection with `continue-on-error: true` and a
comment promising to flip it "once we've seen the first measured number and
set a threshold (ticket 201)". Ticket 201 shipped something else; five sprints
later the threshold was never set, so mutation testing has gated nothing since
the project began. A 200-test suite with an unmeasured MSI can hide large
numbers of assertion-free tests.

## Goal

Infection fails the build when the mutation score drops below a recorded,
justified threshold.

## Acceptance criteria

- [ ] The current MSI and covered-code MSI are measured on main *after*
      tickets 601/602 merge, and both numbers are recorded in this ticket's
      decisions log.
- [ ] `infection.json5` sets `minMsi` and `minCoveredMsi` at (measured − 2)
      points, so the gate catches regressions without flaking.
- [ ] `continue-on-error: true` is removed from the Infection step; the stale
      "ticket 201" comment is deleted.
- [ ] Surviving mutants in Core and Frontend are triaged: each is either
      killed with a new assertion or listed in the decisions log with a
      one-line justification (e.g. logging-only mutant).
- [ ] `composer qa` docs in composer.json's `scripts-descriptions` updated to
      reflect that infection now gates in CI.

## Out of scope

- Chasing 100% MSI; the goal is a ratchet, not a vanity number.

## Dependencies

- **Blocks:** 699
- **Blocked by:** 601, 602 (measure after the coverage gaps close, or the
  threshold is set against a known-weak baseline)
- **External:** none

## Notes / decisions log

- {date} — measured MSI: __ / covered MSI: __

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
