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
| **Sprint 4 — Resilience & empty-state fallbacks** | Done | 401–406 implemented and tested: empty-state fallbacks (401/402), Google OAuth foundation (404), Search Console source (405), optional GA4 source (406). 499 = GO (qa green, 192 tests); live Google smoke deferred (needs real credentials). |
| **Sprint 5 — wp.org submission compliance** | Done | 501 completed: runtime-only packaging, direct-file guards, branded naming, and external-services readme disclosure. |
| **Sprint 6 — Trust & Scale** | In progress | 601–617 completed; 699 No-Go. Google wizard audit filed 707–713 (wp.org disclosure + functional). |

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
- [`docs/GOOGLE.md`](../docs/GOOGLE.md) — Google top-content sourcing setup (Search Console + GA4, tickets 404–406).
- [`sprint-2-implementation-audit-2026-06-18.md`](sprint-2-implementation-audit-2026-06-18.md)
  — code-vs-ticket audit of 201–205.
- [`plugin-audit-2026-07-21.md`](plugin-audit-2026-07-21.md)
  — pre-Sprint-6 review gate audit (spawned 611–615).
- [`google-wizard-audit-2026-07-22.md`](google-wizard-audit-2026-07-22.md)
  — full-page Google wizard / GA4 picker audit (spawned 707–713).

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
- [x] [403 — Google GA4 / Search Console "top content" sourcing (exploratory spike)](completed/403-google-analytics-search-console-top-content.md)
- [x] [404 — Google OAuth foundation + secure settings for top-content sourcing](completed/404-google-oauth-foundation-secure-settings.md)
- [x] [405 — Search Console cached top-content source for Blog fallback](completed/405-search-console-cached-top-content-source.md)
- [x] [406 — GA4 top-content source (optional second Google signal)](completed/406-ga4-top-content-source.md)
- [x] [499 — Sprint 4 review gate (GO; qa green, live Google smoke deferred)](completed/499-sprint-4-review.md)

## Sprint 5 — wp.org submission compliance

The first WordPress.org audit surfaced review-facing issues that are separate
from ordinary code quality: the distributable ZIP was shipping development
dependencies, the first-party PHP files lacked direct-access guards, the public
name was still generic, and the readme did not disclose the optional Google
services. Sprint 5 closes those submission blockers.

- [x] [501 — WordPress.org audit remediation](completed/501-wordpress-org-audit-remediation.md)
- [x] [502 — Autoloader PHPCS cleanup](completed/502-autoloader-phpcs-cleanup.md)

## Sprint 6 — Trust & Scale

Sprint 6 closes the gap between the stated quality bar and the surfaces not yet
covered by it: the public search endpoint, OAuth lifecycle, real-WordPress
behavior, release packaging, scale, accessibility, and SEO interoperability.
See the [2026-07-21 plugin audit](plugin-audit-2026-07-21.md) for evidence and
priority.

- [x] [601 — Unit tests for ArchiveSearchEndpoint](completed/601-archive-search-endpoint-tests.md)
- [x] [602 — Unit tests for Google admin controllers & settings surface](completed/602-google-admin-controller-tests.md)
- [x] [603 — Real-WordPress integration test rig](completed/603-real-wordpress-integration-rig.md) (follow-up: 704)
- [x] [604 — Infection minMsi threshold as a merge gate](completed/604-infection-msi-gate.md)
- [x] [605 — SecretCipher hardening (AEAD, fail-loud, key rotation)](completed/605-secretcipher-hardening.md)
- [x] [606 — uninstall.php: options, transients, token revocation](completed/606-uninstall-cleanup.md)
- [x] [607 — CI version matrix + repo hygiene](completed/607-ci-matrix-repo-hygiene.md)
- [x] [608 — Performance at scale (bounded queries, caching, throttle)](completed/608-performance-at-scale.md)
- [x] [609 — Archive accessibility pass (WCAG 2.2 AA)](completed/609-archive-accessibility.md) (follow-up: 702)
- [x] [610 — i18n completeness + wp.org listing assets](completed/610-i18n-wporg-listing.md) (follow-up: 703)
- [x] [611 — Restore release gate + runtime-only package](completed/611-release-branch-stabilisation.md) (follow-up: 616)
- [x] [612 — Archive-tail redirects + Hybrid cache invalidation](completed/612-archive-route-cache-correctness.md) (follow-up: 617)
- [x] [613 — Admin settings UI integrity + accessibility](completed/613-admin-settings-ui-integrity.md)
- [x] [614 — Google OAuth least privilege + revocation](completed/614-google-oauth-least-privilege-lifecycle.md)
- [x] [615 — SEO plugin interoperability + canonical ownership](completed/615-seo-plugin-interoperability.md)
- [x] [616 — Restore PHP 8.1 Composer-lock compatibility](completed/616-php81-composer-lock-compatibility.md)
- [x] [617 — Handle rejected archive-tail safe redirects](completed/617-archive-tail-safe-redirect-fallback.md)
- [ ] [699 — Sprint 6 review gate (including deferred live Google smoke)](699-sprint-6-review.md)

Google wizard audit (2026-07-22) — filed against the uncommitted full-page
wizard / GA4 picker work; see
[google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md):

- [x] [707 — wp.org: External services disclosure for wizard Analytics scope + Admin API](707-wporg-google-external-services-disclosure.md)
- [x] [708 — Uninstall user-scoped SC/GA4 property-list transients](708-uninstall-property-list-transients.md)
- [x] [709 — Replace News panel `<insert newscycle settings>` placeholder copy](709-news-panel-placeholder-copy.md)
- [x] [710 — Enable Analytics Admin API in wizard + surface GA4 list failures](710-ga4-admin-api-instructions-and-errors.md)
- [x] [711 — Force reconnect when upgrading SC-only → SC+GA4](711-wizard-ga4-scope-upgrade-reconnect.md)
- [x] [712 — Preserve `cf_signal` on wizard Back / checklist links](712-wizard-signal-navigation.md)
- [x] [713 — Remove leftover Property-step “Save property and continue” CTA](713-wizard-property-duplicate-save.md)
- [x] [714 — Make the dynamic-transient uninstall query pass Plugin Check](714-wporg-plugin-check-uninstall-query.md)

Pre-submission audit (2026-07-23) — filed before WordPress.org submission;
these are release-readiness fixes and block ticket 699 unless noted otherwise:

- [x] [715 — Complete Google external-services disclosure for WordPress.org](715-wporg-google-external-services-complete-disclosure.md)
- [x] [716 — Make the readme accurately describe archive URLs and pagination integration](716-readme-archive-behaviour-accuracy.md)
- [x] [717 — Remove the search-cache generation option on uninstall](717-uninstall-search-cache-generation-option.md)
- [x] [718 — Replace PageRank-sculpting language in the public listing copy](718-readme-neutral-seo-positioning.md)
- [x] [719 — Align the plugin-header and readme license declarations](719-align-plugin-license-declarations.md)
- [x] [720 — Make the PHP compatibility claim match supported runtimes and CI](720-php85-support-claim-and-ci.md)
- [x] [721 — Bring the 0.1.1 changelog in line with the distributed plugin](721-update-011-changelog-for-shipped-google-features.md)
- [x] [722 — Remove unused branding assets from the distributable ZIP](722-remove-unused-distribution-brand-assets.md)

Also fixed mid-sprint, discovered independently by six of the tickets above:
[a `wp_safe_redirect` test-shim collision](completed/618-phpunit-shim-collision-silently-truncated-suite.md)
that silently truncated the whole PHPUnit run after ~8 tests while still
exiting 0 — every "`composer qa` is green" claim before this fix (this
sprint and possibly earlier ones) was unverified past that point.

## Sprint 7 — Modernisation (proposed)

Candidates filed as genuine out-of-scope discoveries during Sprint 6, not
yet scheduled. See ticket 699's own "out of scope" note for the REST-migration
and Playwright candidates also expected to land here.

- [ ] [701 — PHPUnit 10/11 and PHPStan 2.x toolchain modernisation](701-phpunit-phpstan-toolchain-modernisation.md)
- [ ] [702 — Non-text (UI component) contrast for the front-end theme's border colour](702-archive-theme-non-text-contrast.md)
- [x] [703 — Capture wp.org listing screenshots](completed/703-wporg-screenshot-capture.md)
- [x] [706 — Make the disposable integration rig rebuild-safe](completed/706-integration-rig-build-order.md)
- [x] [723 — Add opt-in full-site pagination after the optimised archive page](723-opt-in-full-archive-pagination.md)
