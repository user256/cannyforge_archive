# Ticket 724: Bound the full-archive continuation query

**Sprint:** 7 — Modernisation (proposed)
**Status:** Complete
**Owner:** Codex
**Estimate:** M
**Priority:** P0 — pre-submission correctness and scale

---

## Context

The ticket-723 implementation described each `WP_Query` batch as bounded, but
issued as many batches as necessary to read the entire published archive. It
mapped and retained every eligible post before slicing the requested 50-entry
page. A 50,000-post site therefore performed roughly 250 WordPress queries and
hydrated up to 50,000 entries for one continuation response.

This contradicted the intended whole-database search design, which obtains an
exact total with a one-row `fields => ids` query and fetches only the requested
result page.

## Goal

Make full-archive continuation use constant query/result cardinality per
request while preserving its ordering, content-selection, page-one exclusion,
and out-of-range behavior.

## Acceptance criteria

- [x] Eligibility rules are translated to `tax_query` / `meta_query` constraints
      and applied by the database before pagination.
- [x] The total-count query requests one ID and reads `found_posts`; it never
      materializes the matching archive.
- [x] The result query fetches at most 50 posts for exactly one requested page
      and skips its own duplicate found-row calculation.
- [x] Archive page one uses a one-ID existence query rather than building page
      two merely to decide whether to render its link.
- [x] Page-one local posts remain excluded by stable post ID; external URLs do
      not suppress local posts.
- [x] Deterministic date/ID ordering and out-of-range 404 behavior are retained.
- [x] Unit coverage locks the page/count query shapes and database constraints.
- [x] Real-WordPress integration covers category and noindex filtering.
- [x] Plugin Check has no errors or warnings for the distributable.

## Notes / decisions log

- 2026-07-23 — Replaced the batched full scan with
  `FullArchiveQueryArgsBuilder`: one `found_posts` count query plus one bounded
  page query. The existing `post__not_in` parameter is retained for the finite
  set of local IDs actually rendered on promoted page one; this is the native
  WordPress mechanism needed to preserve stable-ID deduplication.
- 2026-07-23 — Content selection is now expressed before paging. Include labels
  match category or tag names, exclusions reject either taxonomy, and supported
  Yoast/Rank Math noindex flags are excluded with an optional meta query.
- 2026-07-23 — Verification: full local QA passed with 403 tests / 1,210
  assertions; disposable real-WordPress integration passed with 14 tests / 126
  assertions, including the new category-plus-noindex and 50-entry boundary
  cases.

---

## Definition of done

1. All acceptance criteria are checked.
2. The distributable ZIP is rebuilt from the corrected source.
3. Local QA, real-WordPress integration, and Plugin Check are green.
