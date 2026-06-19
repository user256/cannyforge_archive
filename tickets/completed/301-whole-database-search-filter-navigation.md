# Ticket 301: Whole-database search & filter navigation

**Sprint:** 3 — Findability
**Status:** Done
**Owner:** unassigned
**Estimate:** L

---

## Context

The archive page is meant to do two distinct jobs that the current code conflates:

1. **Promote** — the HTML-sitemap view shows a curated/promoted set (newest in News
   mode, top URLs in Blog mode, or a combination). This is the PageRank-sculpting
   surface and is intentionally bounded.
2. **Make everything findable** — a user genuinely looking for content must be able
   to search and filter the **whole site database**, and navigate it paginated by
   category/tag/author/date — *not* be limited to filtering the promoted subset.

Today the search and filters (ticket 106, `archive-filters.js`) operate purely
client-side over the DOM nodes the server rendered — i.e. only the promoted
entries (`NewsEntryProvider` recent window, `BlogEntryProvider` top-N). The filter
dropdown options are also derived only from those entries
(`FilterControlsRenderer`). So a user searching for an older or non-promoted
article finds nothing: we are filtering what we already chose to show, instead of
letting them discover the whole site. That defeats the findability half of the
product's purpose.

## Goal

When a user searches or applies a filter on the archive page, the results come
from a paginated query over the **entire content database**, while the default
(no query) view remains the promoted HTML sitemap.

## Acceptance criteria

- [x] Default archive view (no active search/filter) renders the promoted set
      exactly as before — crawlable, no-JS, unchanged markup.
- [x] Activating any search term or filter swaps the same page to a results view
      backed by a query over **all** published content, not just promoted entries.
- [x] Results are **paginated** (page size configurable; sensible default) and
      navigable (next/prev or numbered) without losing the active filter/search.
- [x] Filter dropdown options (category, tag, author, month+year) are sourced from
      the **whole database**, not only the promoted entries.
- [x] A new AJAX endpoint serves filtered/paginated results; it is
      capability-/nonce-appropriate for a public read endpoint, sanitises all
      inputs, and returns a bounded page of results.
- [x] Query logic (filter→WP_Query args, pagination math) lives in a pure,
      WordPress-free method covered by PHPUnit, consistent with existing providers.
- [x] `composer qa` passes (PHPCS, PHPStan, Rector, phparkitect, deptrac, phpmd,
      PHPUnit).
- [x] Verified live at `http://127.0.0.1/archive/` against a large seeded dataset:
      searching for a deep/old post title returns it; the promoted default view
      does not list it.

## Out of scope

- Changing what counts as "promoted" (News window logic, Blog top-N selection).
- Full-text relevance ranking / fuzzy search — straightforward title/content
  matching via `WP_Query` `s` is sufficient here.
- Replacing the existing no-JS crawlable sitemap with a JS-only experience; the
  promoted default must stay server-rendered.

## Dependencies

- **Blocks:** none.
- **Blocked by:** none. (Benefits from 302 large-dataset seeding for verification.)
- **External:** local WP install at `/var/www/html` for live verification.

## Approach

- Add a `ContentIndexProvider` (or similar) that builds **pure** `WP_Query` args
  from a filter/search/pagination request DTO, and a thin runner that executes it
  — mirroring `NewsEntryProvider`'s pure-args + isolated-run split so the logic is
  unit-testable without WordPress.
- Register an AJAX endpoint (admin-ajax or REST) that accepts sanitised filter +
  page params, runs the provider, and returns rendered entry HTML (or JSON the
  client renders) plus pagination metadata.
- Update `FilterControlsRenderer` to source dropdown options from the whole-DB
  index, not the promoted entries.
- Update `archive-filters.js`: default = promoted DOM (no fetch); on
  filter/search/pagination change, fetch from the endpoint and swap the results
  region, preserving state in the URL where practical.

## Notes / decisions log

- 2026-06-19 — **AJAX decision reversed deliberately.** Tickets 106/205 chose
  client-side-only filtering and 205's wording was corrected to "client-side, not
  AJAX." That was correct for filtering the *promoted* set, but cannot satisfy
  whole-database findability without shipping the entire DB to the client. This
  ticket introduces an AJAX endpoint by design. Recorded so the history is
  coherent.
- 2026-06-19 — Page model: same page swaps view (promoted → full results) on
  active query, per product owner.
- 2026-06-19 — **Live-verified** against 1022 posts seeded across 2004–2026
  (`seed-historic-content.sh --count 1000 --days-step 8`). News mode / 72h window:
  the promoted view rendered 16 recent entries and did **not** contain the oldest
  (2004) post; the search endpoint found that 2004 post (total 1), a category
  filter returned 250 posts across 13 pages, a `2004-07` month filter found the
  deep post, page-2 navigation reported correct has_prev/has_next, and a bad nonce
  returned HTTP 403. Promotion and findability are now cleanly separated.
- 2026-06-19 — Added `--days-step` to the seeder so a high `--count` packs into a
  controlled date range (1000 posts ≈ 22 years) for depth testing.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
