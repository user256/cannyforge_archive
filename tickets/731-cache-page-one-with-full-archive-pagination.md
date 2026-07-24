# Ticket 731: Fragment-cache page one when full-archive pagination is enabled

**Sprint:** 7 — Modernisation (performance follow-up)
**Status:** Complete
**Owner:** unassigned
**Estimate:** M
**Priority:** P2 — avoid uncached archive homepage under the new opt-in

---

## Context

Ticket 723 deliberately bypasses the promoted HTML fragment cache when
`full_archive_pagination` is on so continuation membership cannot leak through
a stale HTML blob. `ArchivePage::build_page_one_html()` therefore rebuilds the
promoted entry list, writes page-one IDs, and runs a continuation existence
query on **every** `/archive/` hit.

That is correct, but it makes the most-crawled archive URL the most expensive
one precisely when a site owner enables the “complete archive” feature. Page-one
ID membership is already cached separately; the missing piece is a safe HTML
cache key/invalidation that also captures “has continuation link or not.”

## Goal

With full-archive pagination enabled, `/archive/` is served from a fragment
cache that cannot leak disabled-mode HTML, wrong membership, or a stale
“Browse the full archive” link.

## Acceptance criteria

- [x] Page-one HTML under full-archive mode is cached (or equivalently cheap on
      repeat views) without re-querying the full promoted provider on every hit.
- [x] Cache invalidation still clears on settings save, post/term/author changes,
      and mode switches (existing `CacheInvalidator` contract).
- [x] Toggling `full_archive_pagination` never serves the other mode’s HTML.
- [x] The continuation CTA appears only when `has_continuation` is true for the
      current page-one membership; tests cover CTA present/absent around
      membership changes.
- [x] Unit tests lock the cache key / invalidation behaviour; no integration
      regression in `FullArchivePaginationTest`.

## Out of scope

- Caching continuation pages `/archive/page/N/` (optional later ticket).
- Changing default-off behaviour of the setting.

## Dependencies

- **Blocks:** none
- **Blocked by:** 723–725 (behaviour in tree)
- **External:** none

## Notes / decisions log

- 2026-07-24 — Page-one HTML cached under _full key; uninstall inventory updated.

- 2026-07-24 — Filed from [plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md).
  Not a wp.org rejection item; filed because enabling the featured 0.1.1
  continuation path currently disables the main archive cache on purpose.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox marked done.
4. Follow-ups filed, not absorbed.
