# Ticket 725: Bound whole-database filter-option queries

**Sprint:** 7 — Modernisation (proposed)
**Status:** Complete
**Owner:** Codex
**Estimate:** M
**Priority:** P0 — pre-submission scale correctness

---

## Context

`FilterOptionsProvider::months()` requested every published post ID and mapped
each ID back to a date merely to produce one option per distinct month. The
archive page also built category, tag, author, and month option lists even when
their corresponding controls were disabled.

Categories and tags already use WordPress's term query and therefore return one
object per actual option, not one row per post. Authors similarly return one
published author per option. Months were the outlier: output cardinality was
small while input transfer and PHP work grew with every post.

## Goal

Make filter-option work proportional to the enabled controls and their distinct
option counts, never the number of published posts.

## Acceptance criteria

- [x] Disabled category, tag, author, and month controls issue no option query.
- [x] Categories/tags continue to use WordPress's cached `get_terms()` API.
- [x] Authors continue to use WordPress's cached `get_users()` API and are not
      queried under the default author-off configuration.
- [x] Months come from one `DISTINCT DATE_FORMAT(post_date, '%Y-%m')` query,
      returning one value per distinct month rather than every post ID.
- [x] Month results are object-cached under the core posts last-changed token,
      so post changes naturally advance the cache generation.
- [x] Malformed database values are rejected before option rendering.
- [x] Unit coverage proves distinct-query shape, cache reuse/invalidation, and
      disabled-dimension query suppression.
- [x] The query executes correctly against the disposable MariaDB WordPress
      database and Plugin Check reports no errors or warnings.

## Notes / decisions log

- 2026-07-23 — Categories, tags, and authors are not post-cardinality scans:
  their result cardinality equals the dropdown cardinality the UI must render.
  If a site has thousands of terms or authors, the dropdown itself should
  become a typeahead/search UI; silently truncating its options would make
  whole-site filtering incomplete.
- 2026-07-23 — Month retrieval now uses a prepared identifier/value query and
  the same posts last-changed generation WordPress core uses for post-derived
  caches. Real WordPress verification returned all distinct seeded months with
  one database query and zero further queries on the cached call.
- 2026-07-23 — Full local QA passed with 407 tests / 1,223 assertions. Plugin
  Check completed with no errors or warnings.

---

## Definition of done

1. All acceptance criteria are checked.
2. The distributable ZIP is rebuilt from the corrected source.
3. Local QA, real-WordPress execution, and Plugin Check are green.
