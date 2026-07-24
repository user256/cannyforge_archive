## Sprint 1 — Settings & MVP

**Status:** Closed

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

## Sprint 2 — Hardening & Fit

**Status:** Closed

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

## Sprint 3 — Findability

**Status:** Closed

The archive does two distinct jobs that the Sprint 1/2 code conflated. The
**HTML-sitemap page** shows the *promoted* set (newest / best / a combination) —
the PageRank-sculpting surface. The **search and filters** must let a user
discover the *whole site database*, paginated by category/tag/author/date — not
just filter the promoted subset. Sprint 3 separates these two concerns.

- [x] [301 — Whole-database search & filter navigation](completed/301-whole-database-search-filter-navigation.md)
- [x] [399 — Sprint 3 review gate (GO)](completed/399-sprint-3-review.md)

## Sprint 4 — Resilience & empty-state fallbacks

**Status:** Closed

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

**Status:** Closed

The first WordPress.org audit surfaced review-facing issues that are separate
from ordinary code quality: the distributable ZIP was shipping development
dependencies, the first-party PHP files lacked direct-access guards, the public
name was still generic, and the readme did not disclose the optional Google
services. Sprint 5 closes those submission blockers.

- [x] [501 — WordPress.org audit remediation](completed/501-wordpress-org-audit-remediation.md)
- [x] [502 — Autoloader PHPCS cleanup](completed/502-autoloader-phpcs-cleanup.md)

## Sprint 6 — Trust & Scale

**Tickets:**
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
- [x] [707 — wp.org: External services disclosure for wizard Analytics scope + Admin API](707-wporg-google-external-services-disclosure.md)
- [x] [708 — Uninstall user-scoped SC/GA4 property-list transients](708-uninstall-property-list-transients.md)
- [x] [709 — Replace News panel `<insert newscycle settings>` placeholder copy](709-news-panel-placeholder-copy.md)
- [x] [710 — Enable Analytics Admin API in wizard + surface GA4 list failures](710-ga4-admin-api-instructions-and-errors.md)
- [x] [711 — Force reconnect when upgrading SC-only → SC+GA4](711-wizard-ga4-scope-upgrade-reconnect.md)
- [x] [712 — Preserve `cf_signal` on wizard Back / checklist links](712-wizard-signal-navigation.md)
- [x] [713 — Remove leftover Property-step “Save property and continue” CTA](713-wizard-property-duplicate-save.md)
- [x] [714 — Make the dynamic-transient uninstall query pass Plugin Check](714-wporg-plugin-check-uninstall-query.md)
- [x] [715 — Complete Google external-services disclosure for WordPress.org](715-wporg-google-external-services-complete-disclosure.md)
- [x] [716 — Make the readme accurately describe archive URLs and pagination integration](716-readme-archive-behaviour-accuracy.md)
- [x] [717 — Remove the search-cache generation option on uninstall](717-uninstall-search-cache-generation-option.md)
- [x] [718 — Replace PageRank-sculpting language in the public listing copy](718-readme-neutral-seo-positioning.md)
- [x] [719 — Align the plugin-header and readme license declarations](719-align-plugin-license-declarations.md)
- [x] [720 — Make the PHP compatibility claim match supported runtimes and CI](720-php85-support-claim-and-ci.md)
- [x] [721 — Bring the 0.1.1 changelog in line with the distributed plugin](721-update-011-changelog-for-shipped-google-features.md)
- [x] [722 — Remove unused branding assets from the distributable ZIP](722-remove-unused-distribution-brand-assets.md)

## Sprint 7 — Modernisation (proposed)

**Tickets:**
- [x] [703 — Capture wp.org listing screenshots](completed/703-wporg-screenshot-capture.md)
- [x] [706 — Make the disposable integration rig rebuild-safe](completed/706-integration-rig-build-order.md)
- [x] [723 — Add opt-in full-site pagination after the optimised archive page](723-opt-in-full-archive-pagination.md)
- [x] [724 — Bound the full-archive continuation query](724-bound-full-archive-continuation-query.md)
- [x] [725 — Bound whole-database filter-option queries](725-bound-filter-option-queries.md)
- [x] [729 — Do not treat exhausted full-archive URLs as indexable archive requests](729-no-seo-on-exhausted-full-archive-404s.md)
- [x] [730 — Make full-archive content-selection matching match page-one semantics](730-align-full-archive-content-selection-matching.md)
- [x] [731 — Fragment-cache page one when full-archive pagination is enabled](731-cache-page-one-with-full-archive-pagination.md)
- [x] [726 — Ship the remote Google Fonts removal and keep the packaging guard](726-ship-remote-font-css-removal.md)
- [x] [727 — Remove dead Google wizard modal and fixture code from the distributable](727-remove-dead-wizard-modal-and-fixture-code.md)
- [x] [728 — Drop the residual crawl-budget readme tag](728-drop-crawl-budget-readme-tag.md)
- [x] [729 — Do not treat exhausted full-archive URLs as indexable archive requests](729-no-seo-on-exhausted-full-archive-404s.md)
- [x] [730 — Make full-archive content-selection matching match page-one semantics](730-align-full-archive-content-selection-matching.md)
- [x] [731 — Fragment-cache page one when full-archive pagination is enabled](731-cache-page-one-with-full-archive-pagination.md)

