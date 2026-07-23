# Ticket 721: Bring the 0.1.1 changelog in line with the distributed plugin

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** S
**Priority:** P2 — release metadata accuracy

---

## Context

The 0.1.1 changelog lists Sprint 2 hardening but the distributable now includes
substantial optional Google OAuth, Search Console, and GA4 functionality. A
reviewer or early user cannot tell from the release notes what the submitted
version actually introduced.

## Goal

The changelog accurately summarises material user-facing functionality shipped
in version 0.1.1 without promising unfinished work.

## Acceptance criteria

- [ ] The 0.1.1 changelog includes the optional Google connection, Search
      Console, and GA4 top-content functionality actually present in the ZIP.
- [ ] The existing hardening and archive changes remain represented accurately.
- [ ] No changelog entry claims a feature that is disabled, incomplete, or only
      planned in another ticket.
- [ ] Readme validation passes.

## Out of scope

- Changing the release version or publishing a new release.
- Rewriting older historical changelog entries.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** 715 only insofar as the final wording must not contradict the
      corrected external-service disclosure.
- **External:** none

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Future release notes are updated in the same release change.
