# Ticket 105: Blog mode — manual / CSV top-URL list

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

In Blog mode the archive includes a curated set of top URLs. The brief scopes
the **MVP** to manual text entry or CSV import (the Snowflake/Adobe automation
is a *final-version* concern). This is the Blog-mode entry provider feeding the
generator (ticket 103).

## Goal

An entry provider that turns a user-supplied / CSV-imported URL list (capped at
the configured maximum) into archive entries.

## Acceptance criteria

- [ ] Implements the entry-provider contract from ticket 103.
- [ ] Accepts the URL list from settings (textarea) and a CSV import path;
      parses, trims, de-duplicates, and validates URLs.
- [ ] Caps the list at `blog_max_urls` (default 100).
- [ ] Resolves each URL to a displayable entry (title/description/featured image
      as available) so the link-type toggles still apply.
- [ ] `composer test` covers parsing (mixed valid/invalid lines), de-duplication,
      and the cap.

## Out of scope

- Snowflake / Adobe popularity sourcing (final version — separate sprint).
- News recent-window query (ticket 104).

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
