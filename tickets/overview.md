# Project Overview — CannyForge Archive

This is the central roadmap for the plugin. The product brief lives in
[`docs/PLAN.md`](../docs/PLAN.md); the design system in
[`docs/DESIGN.md`](../docs/DESIGN.md).

## Programme Status

| Area | State | Outstanding / next |
|------|--------|---------------------|
| **Scaffold & quality gates** | Done | Layered `src/` (Contracts/Core/Bootstrap), full `composer qa` gate. |
| **Sprint 1 — Settings & MVP** | Planned | Admin settings page, archive/sitemap generator, pagination replacement. |

## Sprint 1 — Settings & MVP

The MVP per the brief: an admin settings page, a generated archive page that
doubles as an HTML sitemap, and the limited-pagination replacement. Blog mode
uses manual / CSV URL entry (the Snowflake/Adobe integration is explicitly a
*final-version* concern, out of scope for the MVP).

- [x] 101 — Settings model & persistence (Core + Contracts)
- [ ] 102 — Admin settings page UI (Blog/News toggle, link-types, filters)
- [ ] 103 — Archive / HTML-sitemap page generator
- [ ] 104 — News mode: recent-window content query
- [ ] 105 — Blog mode: manual / CSV top-URL list
- [ ] 106 — Client-side search & filters (search, category, tag, month+year, author)
- [ ] 107 — Pagination replacement with "View Archive" link
- [ ] 108 — Branding & admin styling (CannyForge design system)
- [ ] 199 — Sprint 1 review gate (Go / No-Go)

## Ticket numbering

- Each sprint owns a hundred-block: Sprint 1 = `1xx`, Sprint 2 = `2xx`, etc.
- Work tickets start at `N01`. Insert mid-sprint additions at the next free number.
- **`N99` is always the sprint review gate** — the mandatory Go/No-Go.

Use [`tickets/TICKET_TEMPLATE.md`](TICKET_TEMPLATE.md) to create new tickets.
When done, move them to [`tickets/completed/`](completed/).
