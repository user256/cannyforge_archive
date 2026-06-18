# Ticket 107: Pagination replacement with "View Archive" link

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The other half of the brief: replace WordPress's default taxonomy pagination
(`Previous « 1 2 3 4 5 … » Next`) with a shorter, fixed sequence that links out
to the archive (`1 2 3 4 5 6 7 8 9  View Archive →`). This is what conserves
crawl budget and routes crawlers to the single rich archive page (ticket 103).

## Goal

A pagination renderer that shows at most the configured number of page links,
then a "View Archive" link instead of the long default tail.

## Acceptance criteria

- [ ] On supported archive listings, the default paginated tail is replaced
      with a sequence capped at `pagination_limit` (default 1) followed by a
      "View Archive" link.
- [ ] The "View Archive" link destination is configurable (defaults to the
      ticket-103 archive endpoint).
- [ ] The replacement only applies to the archive types enabled in the
      targeting controls (ticket 109); it never hijacks unrelated paginated
      views, and does not double-render alongside the theme's default.
- [ ] Beyond the limit, deep page links are not emitted (the crawl-budget goal).
- [ ] `composer test` covers link-count for several `pagination_limit` values,
      that the archive link target is correct (including a configured override),
      and that an unsupported/disabled archive type is left untouched.

## Out of scope

- The archive page itself (ticket 103).
- Which archive types are eligible — that selection is ticket 109; this ticket
  only consumes it.
- Theme-specific styling beyond ticket 108.

## Dependencies

- **Blocks:** none
- **Blocked by:** 101 (limit setting), 103 (archive URL to link to),
  109 (archive-type targeting — which listings the replacement applies to)
- **External:** none

## Notes / decisions log

- 2026-06-18 — Confirmed product decisions: pagination limit stays default 1;
  the archive-link destination is configurable; the replacement is restricted
  to the archive types selected in the targeting controls (ticket 109).

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
