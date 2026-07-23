# Ticket 723: Add opt-in full-site pagination after the optimised archive page

**Sprint:** 7 — Modernisation (proposed)
**Status:** Complete
**Owner:** unassigned
**Estimate:** L
**Priority:** P2 — archive completeness and orphan prevention

---

## Context

The archive endpoint currently renders only the optimised/promoted selection:
the configured News window, curated Blog URLs, or their fallbacks. It is an
excellent first archive page, but older eligible posts that are not selected
there have no server-rendered continuation from that page. The existing
whole-database search endpoint is JavaScript-driven and is not a crawlable
date-ordered archive sequence.

Site owners need an optional complete archive: page one stays the carefully
optimised archive, while subsequent pages expose every remaining eligible post
newest-to-oldest so content is not orphaned. This must be off by default so the
current product and URLs remain unchanged unless an administrator elects it.

## Goal

When explicitly enabled, `/archive/` remains the optimised first page and a
canonical, server-rendered sequence of later archive pages lists every remaining
eligible post in descending publication-date order without duplicating page-one
content.

## Acceptance criteria

- [x] Add a clearly labelled boolean setting, default **off**, with an
      explanatory admin control. Existing installations and fresh installs keep
      today’s `/archive/` output and tail handling while it is off.
- [x] With the setting on, `/archive/` is page one and continues to render the
      existing optimised/promoted selection unchanged.
- [x] With the setting on, canonical later pages use `/archive/page/2/`,
      `/archive/page/3/`, and so on. `/archive/page/1/` redirects canonically
      to `/archive/`; malformed/out-of-range page paths follow an explicit,
      tested policy and never produce duplicate 200 pages.
- [x] Page 2 onward is server-rendered and works without JavaScript. It queries
      all published `post` records eligible under the existing archive
      content-selection rules, orders them by publication date descending (with
      a deterministic tie-breaker), and excludes every local post that was
      actually rendered on page one.
- [x] The exclusion/deduplication mechanism is based on stable local post IDs,
      not display URLs. Curated external URLs do not suppress unrelated local
      posts. The selected policy for non-post content and manually curated URLs
      is documented.
- [x] Later-page navigation is accessible, exposes previous/next and page
      relationships, and links only to valid pages. Page one offers a clear
      route to page two only when eligible older content exists.
- [x] Full-pagination rendering bypasses the promoted-page fragment cache, so no
      enabled/disabled, membership, or later-page response can leak through it;
      page number, and page-one membership so no enabled/disabled or page-one/
      later-page response can leak into another request.
- [x] SEO handling defines canonical URLs and pagination metadata for every
      page, does not conflict with existing SEO-plugin interoperability, and
      retains page-one SEO settings only where appropriate.
- [x] Unit coverage proves ordering, membership exclusion, no duplicates across
      page boundaries, default-off compatibility, and URL normalisation.
- [x] Real-WordPress integration coverage seeds enough posts to prove that the
      optimised first page plus all later pages visits each eligible post exactly
      once, newest-to-oldest after page one. `composer qa` and `composer
      test:integration` pass.
- [x] The readme and usage documentation describe
      the opt-in behaviour and its URL structure accurately.

## Out of scope

- Replacing the existing taxonomy-pagination replacement or changing its
      `pagination_limit` setting.
- Making client-side search/filter result pages crawlable; they remain a
      separate whole-database discovery surface.
- Adding custom post types, pages, or external URLs to the full-site sequence
      without a separate product decision.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Approach (optional)

Introduce a dedicated server-side continuation provider rather than reusing the
AJAX response directly. Derive the exact page-one local post-ID set after the
normal selection pipeline, then run a bounded, date-descending `WP_Query` for
later pages with that set excluded. Extend the endpoint routing before the
current generic archive-tail redirect so valid page paths are recognised while
the disabled default preserves current behaviour.

## Notes / decisions log

- 2026-07-23 — Filed from the product request. "Full site" is interpreted as
  all eligible published WordPress posts after existing content-selection rules;
  page one is never reordered or diluted to make pagination simpler.
- 2026-07-23 — Implemented as an explicit default-off server-rendered
  continuation. Page-one membership is read from stable local IDs after the
  normal entry filter; later pages query bounded batches in WordPress's
  descending date/ID order and apply the existing inclusion/exclusion/noindex
  rules without applying the page-one pin preference.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any broader post-type or search-navigation expansion is filed separately.
