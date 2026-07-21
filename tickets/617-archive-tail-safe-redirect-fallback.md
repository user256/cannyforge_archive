# Ticket 617: Handle rejected archive-tail safe redirects

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S
**Priority:** P0 — correctness

---

## Context

Ticket 612 prevents an empty target, but its destination resolver permits an
external `archive_url`. WordPress can reject that URL in `wp_safe_redirect()`;
the tail handler currently exits without checking the false return.

## Goal

Every rejected archive-tail redirect fails visibly and predictably, never with
a blank response.

## Acceptance criteria

- [ ] Check `wp_safe_redirect()`'s return value in the archive-tail path.
- [ ] On rejection, use a documented safe local fallback or return a 404
      without exiting.
- [ ] Add a regression test that simulates a rejected redirect.
- [ ] Preserve ticket 612's endpoint/destination/SEO-canonical contract.

## Dependencies

- **Blocks:** 699
- **Related:** 612, 603

## Notes / decisions log

- 2026-07-21 — Found during PR #2 review: the implementation checks only for
  an empty resolver value, not whether WordPress accepts the safe redirect.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
