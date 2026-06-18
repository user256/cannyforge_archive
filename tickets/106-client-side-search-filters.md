# Ticket 106: Client-side search & filters

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

The brief specifies user-defined Search/Filters (binary on/off), explicitly
done **client-side using JS** over the already-rendered archive: search box,
category, tag, month+year, and author filters. They enhance the server-rendered
archive (ticket 103) without changing its crawlable output.

## Goal

Progressive-enhancement JS that filters the rendered archive entries by the
enabled controls, with zero impact on the no-JS crawlable output.

## Acceptance criteria

- [ ] Only the filters enabled in settings render and initialise.
- [ ] Search box does live client-side text matching over visible entries.
- [ ] Category, tag, month+year, and author filters narrow the visible set;
      combining filters intersects (AND) as expected.
- [ ] Entries carry the needed filter metadata as data-attributes emitted by
      the ticket-103 generator (so JS needs no extra request).
- [ ] Works as progressive enhancement: with JS disabled, the full archive is
      still present and crawlable.
- [ ] Script is enqueued (not inlined) and only on the archive page.

## Out of scope

- Server-side search (the brief is explicit: client-side).
- Pagination of the filtered set.

## Dependencies

- **Blocks:** none
- **Blocked by:** 103 (needs the rendered entries + data-attributes), 101 (toggles)
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
