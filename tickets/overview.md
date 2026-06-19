# Project Overview — CannyForge Archive

This is the central roadmap for the plugin. The product brief lives in
[`docs/PLAN.md`](../docs/PLAN.md); the design system in
[`docs/DESIGN.md`](../docs/DESIGN.md).

## Programme Status

| Area | State | Outstanding / next |
|------|--------|---------------------|
| **Scaffold & quality gates** | Done | Layered `src/` (Contracts/Core/Admin/Frontend/Bootstrap), full `composer qa` gate. |
| **Sprint 1 — Settings & MVP** | Verified on live WP | 101–111 + 120 (packaging) + 121 (admin UX) done; live smoke passed; 2 defects found & fixed. 199 = GO. |
| **Sprint 2 — Hardening & fit** | Done | 201–208 implemented and tested; 299 = GO. |

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
- [x] 106 — Client-side search & filters (search, category, tag, month+year, author)
- [x] 107 — Pagination replacement with "View Archive" link
- [x] 108 — Branding & admin styling (CannyForge design system)
- [x] 109 — Archive-type targeting controls (where pagination replacement applies)
- [x] 110 — SEO controls (title, meta description, index/follow, canonical)
- [x] 111 — Content selection: include/exclude, noindex, pinned-priority URLs
- [x] 120 — Plugin Check: packaging (.distignore) + WordPress readme.txt
- [x] 121 — Admin UX: CSV import, rename to "Archive Generator", preview link, real branding
- [x] 199 — Sprint 1 review gate (GO; live smoke passed, 2 defects fixed)

## Ticket numbering

- Each sprint owns a hundred-block: Sprint 1 = `1xx`, Sprint 2 = `2xx`, etc.
- Work tickets start at `N01`. Insert mid-sprint additions at the next free number.
- **`N99` is always the sprint review gate** — the mandatory Go/No-Go.

Use [`tickets/TICKET_TEMPLATE.md`](TICKET_TEMPLATE.md) to create new tickets.
When done, move them to [`tickets/completed/`](completed/).

## Sprint 2 — Hardening & Fit

Follow-up hardening from the Sprint-1 live review, plus the packaging, front-end
fit, and test-data work needed to make the plugin easier to ship and evaluate on
real sites.

- [x] [201 — Archive endpoint lifecycle hardening](201-archive-endpoint-lifecycle-hardening.md)
- [x] [202 — Content selection normalisation](202-content-selection-normalisation.md)
- [x] [203 — Distributable build helper + ZIP output](203-distributable-build-helper-zip-output.md)
- [x] [204 — Front-end theming controls](204-front-end-theming-controls.md)
- [x] [205 — Historic-content seeding + archive smoke data](205-historic-content-seeding-archive-smoke-data.md)
- [x] [206 — Fragment caching via transients for HTML sitemap](completed/206-fragment-caching-html-sitemap.md)
- [x] [207 — Extensibility hooks and filters for third-party integrations](completed/207-extensibility-hooks-filters.md)
- [x] [208 — Refactor inline CSS to enqueued stylesheets](completed/208-refactor-inline-css.md)
- [x] [299 — Sprint 2 review gate (GO)](completed/299-sprint-2-review.md)
