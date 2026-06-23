# Project Overview — CannyForge Archive

This is the central roadmap for the plugin. The product brief lives in
[`docs/PLAN.md`](../docs/PLAN.md); the design system in
[`docs/DESIGN.md`](../docs/DESIGN.md); the developer extension points in
[`docs/HOOKS.md`](../docs/HOOKS.md).

## Programme Status

| Area | State | Outstanding / next |
|------|--------|---------------------|
| **Scaffold & quality gates** | Done | Layered `src/` (Contracts/Core/Admin/Frontend/Bootstrap), full `composer qa` gate. |
| **Sprint 1 — Settings & MVP** | Verified on live WP | 101–111 + 120 (packaging) + 121 (admin UX) done; live smoke passed; 2 defects found & fixed. 199 = GO. |
| **Sprint 2 — Hardening & fit** | Done | 201–208 implemented and tested; 299 = GO. |
| **Sprint 3 — Findability** | Done | Separated *promote* (HTML sitemap) from *find* (whole-DB search/filter). 301 done & live-verified; 399 = GO. |
| **Sprint 4 — Resilience & empty-state fallbacks** | In progress | Promoted surface must never be empty when content exists. 401 (News→latest N), 402 (Top→popularity proxy), 403 (GA4/GSC sourcing — exploratory). |

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
- [x] [199 — Sprint 1 review gate (GO; live smoke passed, 2 defects fixed)](completed/199-sprint-1-review.md)

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
- [x] [203 — Distributable build helper + ZIP output](completed/203-distributable-build-helper-zip-output.md)
- [x] [204 — Front-end theming controls](completed/204-front-end-theming-controls.md)
- [x] [205 — Historic-content seeding + archive smoke data](completed/205-historic-content-seeding-archive-smoke-data.md)
- [x] [206 — Fragment caching via transients for HTML sitemap](completed/206-fragment-caching-html-sitemap.md)
- [x] [207 — Extensibility hooks and filters for third-party integrations](completed/207-extensibility-hooks-filters.md)
- [x] [208 — Refactor inline CSS to enqueued stylesheets](completed/208-refactor-inline-css.md)
- [x] [299 — Sprint 2 review gate (GO)](completed/299-sprint-2-review.md)

## Documentation & audits

- [`docs/PLAN.md`](../docs/PLAN.md) — product brief and confirmed product decisions.
- [`docs/DESIGN.md`](../docs/DESIGN.md) — design system.
- [`docs/HOOKS.md`](../docs/HOOKS.md) — developer action/filter hooks (ticket 207).
- [`sprint-2-implementation-audit-2026-06-18.md`](sprint-2-implementation-audit-2026-06-18.md)
  — code-vs-ticket audit of 201–205.

## Sprint 3 — Findability

The archive does two distinct jobs that the Sprint 1/2 code conflated. The
**HTML-sitemap page** shows the *promoted* set (newest / best / a combination) —
the PageRank-sculpting surface. The **search and filters** must let a user
discover the *whole site database*, paginated by category/tag/author/date — not
just filter the promoted subset. Sprint 3 separates these two concerns.

- [x] [301 — Whole-database search & filter navigation](completed/301-whole-database-search-filter-navigation.md)
- [x] [399 — Sprint 3 review gate (GO)](completed/399-sprint-3-review.md)

## Sprint 4 — Resilience & empty-state fallbacks

The promoted archive surface (the HTML sitemap) renders nothing when its
selection query comes up empty: News mode with no post inside the recent window,
or Blog/Top mode with no curated URLs. An archive that can show zero entries
while real content exists defeats its purpose. Sprint 4 makes the promoted set
degrade gracefully to a sensible fallback, and explores sourcing genuine
popularity data from Google (GA4 / Search Console) by lifting the OAuth
infrastructure already proven in `cannyforge-lead-capture`.

- [x] [401 — News empty-state fallback (latest N)](completed/401-news-empty-state-fallback.md)
- [x] [402 — Top/Blog empty-state popularity fallback (comments → Jetpack → newest)](completed/402-top-empty-state-popularity-fallback.md)
- [ ] [403 — Google GA4 / Search Console "top content" sourcing (exploratory)](403-google-analytics-search-console-top-content.md)
