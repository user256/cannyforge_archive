# Ticket 103: Archive / HTML-sitemap page generator

**Sprint:** 1 — Settings & MVP
**Status:** Done
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

- [x] The page is exposed at a stable, configurable URL via a rewrite endpoint
      (default `/archive/`, query var `cannyforge_archive`) and is server-rendered
      on `template_redirect`, crawlable without JS (`Frontend\ArchivePage`).
- [x] Entries render according to the link-type toggles: title link (default on,
      falls back to the URL when off), description and featured image when
      enabled (`Core\Archive\ArchiveRenderer`).
- [x] Entry source is provided through `ArchiveEntryProviderInterface` so it is
      mode-agnostic; the News (104) and Blog (105) providers drop in without
      touching the renderer. Wired with `FixtureEntryProvider` until they land.
- [x] Output is a single accessible `<nav>` + `<ul>`/`<li>` structure suitable
      as an HTML sitemap; every dynamic value is escaped (PHPCS clean). Each
      item also carries `data-categories|tags|author|month` for ticket 106.
- [x] `composer test` covers each link-type combination, the title-off URL
      fallback, the data-attributes, escaping, and the page wiring — against a
      fixture entry set, no live WP query (30 tests / 87 assertions total).

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

- 2026-06-18 — Added `ArchiveEntry` + `ArchiveEntryProviderInterface` to
  `Contracts\Archive` (the shared shape/seam), `ArchiveRenderer` +
  `FixtureEntryProvider` to `Core\Archive`, and a new `Frontend` layer
  (`ArchivePage`) for the WP endpoint. Deptrac updated: Frontend → Contracts +
  Core; Bootstrap → +Frontend.
- 2026-06-18 — Chose a rewrite endpoint over a designated page: no page row to
  manage, a clean crawlable URL, and the renderer stays decoupled from the
  theme loop. Slug is constructor-configurable (default `archive`); the admin
  control to set it can be a follow-up — not required by this ticket.
- 2026-06-18 — `maybe_render()` ends in `exit` (it owns the response), so it is
  not unit-tested directly; the render output is covered via `ArchiveRenderer`,
  and `ArchivePage` tests assert the endpoint/hook registration. Full-request
  rendering is part of the ticket-199 manual smoke.
- 2026-06-18 — Entries carry filter metadata as `data-*` attributes now, so
  ticket 106's client-side filters need no markup change or extra request.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
