# Project Overview — CannyForge Archive

This is the central roadmap for the plugin. The product brief lives in
[`docs/PLAN.md`](../docs/PLAN.md); the design system in
[`docs/DESIGN.md`](../docs/DESIGN.md).

## Programme Status

| Area | State | Outstanding / next |
|------|--------|---------------------|
| **Scaffold & quality gates** | Done | Layered `src/` (Contracts/Core/Admin/Frontend/Bootstrap), full `composer qa` gate. |
| **Sprint 1 — Settings & MVP** | In progress | 101–105 done; archive targeting, SEO, content selection, filters, pagination, branding remaining. |

## Sprint 1 — Settings & MVP

The MVP per the brief and the confirmed product decisions: an admin settings
page, a generated archive page that doubles as an HTML sitemap, the
limited-pagination replacement with configurable archive-type targeting, plus
the SEO and content-selection controls that make this a genuine internal-linking
tool. Blog mode uses manual / CSV URL entry only — analytics-driven URL sourcing
(Snowflake, Adobe, automatic popularity/traffic scoring) is out of scope.

- [x] 101 — Settings model & persistence (Core + Contracts)
- [x] 102 — Admin settings page UI (Blog/News toggle, link-types, filters)
- [x] 103 — Archive / HTML-sitemap page generator
- [x] 104 — News mode: recent-window content query
- [x] 105 — Blog mode: manual / CSV top-URL list
- [ ] 106 — Client-side search & filters (search, category, tag, month+year, author)
- [ ] 107 — Pagination replacement with "View Archive" link
- [ ] 108 — Branding & admin styling (CannyForge design system)
- [ ] 109 — Archive-type targeting controls (where pagination replacement applies)
- [ ] 110 — SEO controls (title, meta description, index/follow, canonical)
- [ ] 111 — Content selection: include/exclude, noindex, pinned-priority URLs
- [ ] 199 — Sprint 1 review gate (Go / No-Go)

## Ticket numbering

- Each sprint owns a hundred-block: Sprint 1 = `1xx`, Sprint 2 = `2xx`, etc.
- Work tickets start at `N01`. Insert mid-sprint additions at the next free number.
- **`N99` is always the sprint review gate** — the mandatory Go/No-Go.

Use [`tickets/TICKET_TEMPLATE.md`](TICKET_TEMPLATE.md) to create new tickets.
When done, move them to [`tickets/completed/`](completed/).
