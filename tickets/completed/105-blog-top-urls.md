# Ticket 105: Blog mode — manual / CSV top-URL list

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

In Blog mode the archive includes a curated set of top URLs, defined by manual
text entry or CSV import. (The original proposal's analytics-driven URL sourcing
is out of scope for this plugin.) This is the Blog-mode entry provider feeding
the generator (ticket 103).

## Goal

An entry provider that turns a user-supplied / CSV-imported URL list (capped at
the configured maximum) into archive entries.

## Acceptance criteria

- [x] Implements the entry-provider contract from ticket 103.
- [x] Accepts the URL list from settings (textarea / CSV import populate the
      `blog_urls` setting); parses, trims, de-duplicates, and validates URLs
      (HTTP(S) only). `select_urls()` is a pure, WP-free method.
- [x] Caps the list at `blog_max_urls` (default 100).
- [x] Resolves each URL to a displayable entry (title/description/featured image
      as available via `url_to_postid`) so the link-type toggles still apply;
      unresolved URLs fall back to a bare entry (title defaults to the URL).
- [x] `composer test` covers parsing (mixed valid/invalid lines), de-duplication,
      and the cap.

## Out of scope

- Analytics-driven URL sourcing (Snowflake, Adobe Analytics, automatic
  popularity scoring, traffic-based selection) — explicitly dropped. The admin
  chooses which URLs appear.
- News recent-window query (ticket 104).
- Include/exclude rules and pinned-priority URLs — those are the cross-mode
  content-selection controls in ticket 111.

## Dependencies

- **Blocks:** 103 (full behaviour)
- **Blocked by:** 101
- **External:** none

## Notes / decisions log

- 2026-06-18 — Implemented `Core/Archive/BlogEntryProvider`. Split pure
  selection (`select_urls`: trim, HTTP(S) validate, de-dupe, cap) from
  WP-touching resolution (`resolve`/`map_post` via `url_to_postid`/`get_post`),
  mirroring `NewsEntryProvider`. Wired into the Blog slot of `ModeEntryProvider`
  in the composition root, replacing the fixture provider (kept as a test seam).
  Note: the settings textarea/CSV intake that populates `blog_urls` is handled
  by the admin form (ticket 102); CSV file import as a distinct upload path is
  not yet built — the parser accepts the comma/newline-separated list either
  source produces. Flagged for follow-up if a dedicated upload UI is wanted.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
