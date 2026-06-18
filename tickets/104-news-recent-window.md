# Ticket 104: News mode — recent-window content query

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

In News mode the archive lists all content published within a recent window
(brief default: 72 hours). This is the News-mode entry provider that feeds the
generator (ticket 103).

## Goal

An entry provider that returns all posts published within the configured
recent window, ordered for archive display.

## Acceptance criteria

- [ ] Implements the entry-provider contract from ticket 103.
- [ ] Honours `news_window_hours` from settings (default 72), reading published
      posts within `now - window`.
- [ ] Query is bounded and ordered (newest first) and uses WP query APIs
      correctly (no raw SQL where avoidable).
- [ ] `composer test` covers window-boundary selection with fixture timestamps
      (a post just inside vs just outside the window).

## Out of scope

- Blog top-URL sourcing (ticket 105).

## Dependencies

- **Blocks:** 103 (full behaviour)
- **Blocked by:** 101
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
