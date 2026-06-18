# Ticket 103: Archive / HTML-sitemap page generator

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

The core deliverable: a single archive page that doubles as an HTML sitemap
(in the spirit of large news sites' HTML sitemaps). It is the destination the
shortened pagination (ticket 107) links out to, and the host for the
client-side filters (ticket 106).

## Goal

A front-end archive page that renders the configured entries as an indexable
HTML sitemap, respecting the link-type toggles.

## Acceptance criteria

- [ ] The page is exposed at a stable, configurable URL (rewrite endpoint or a
      designated page) and is server-rendered (crawlable without JS).
- [ ] Entries render according to the link-type toggles: Title always-ish
      (default on), Description and Featured Image when enabled.
- [ ] Entry source is mode-aware: News mode pulls from the recent-window query
      (ticket 104), Blog mode from the top-URL list (ticket 105).
- [ ] Output is valid, accessible HTML (headings, lists) suitable as an HTML
      sitemap; markup is escaped per WPCS.
- [ ] `composer test` covers entry-list rendering for each link-type combination
      with a fixture entry set (no live WP query in the unit test).

## Out of scope

- Client-side search/filtering (ticket 106) — server output must work without it.
- The pagination replacement (ticket 107).

## Dependencies

- **Blocks:** 106, 107
- **Blocked by:** 101 (settings), 104 + 105 (entry sources) for full behaviour;
  can begin against a fixture entry provider.
- **External:** none

## Approach (optional)

Define an `ArchiveEntry` shape and an entry-provider interface in
`src/Contracts`; the generator (Core) renders providers without knowing whether
they came from News or Blog mode.

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
