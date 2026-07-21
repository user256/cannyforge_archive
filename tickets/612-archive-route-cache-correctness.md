# Ticket 612: Fix archive-tail redirects and Hybrid cache invalidation

**Sprint:** 6 — Trust & Scale
**Status:** In progress
**Owner:** background-agent
**Estimate:** M
**Priority:** P0 — correctness

---

## Context

Two shipped lifecycle guarantees are incomplete. For a non-canonical endpoint
tail, `ArchivePage::maybe_render()` redirects to `Settings::archive_url()`
directly; that setting is optional, so the default configuration attempts a
safe redirect to an empty string and exits instead of resolving to `/archive/`.
Separately, `ArchiveCache` creates a `hybrid` transient but `clear()` deletes
only `blog` and `news`, so Hybrid-mode content/settings changes can remain stale
for the full 24-hour TTL. Existing tests cover neither branch.

## Goal

Every archive endpoint variant resolves predictably and every supported mode's
cached HTML is invalidated when its inputs change.

## Acceptance criteria

- [ ] A non-empty archive endpoint tail redirects to the canonical archive
      endpoint URL when no destination override is configured; it never calls
      `wp_safe_redirect()` with an empty target.
- [ ] The intended relationship between `archive_url` (pagination destination),
      the archive endpoint, and the SEO canonical override is documented and
      represented by one tested URL resolver rather than three subtly different
      implementations.
- [ ] Redirect failure is handled explicitly (fallback 404 or canonical local
      redirect) rather than followed by an unconditional blank-page `exit`.
- [ ] `ArchiveCache::clear()` deletes cache entries for every `Mode` case,
      including `hybrid`, without a hand-maintained list that silently drifts
      when another mode is added.
- [ ] Cache invalidation tests cover settings saves, post changes, and all mode
      values; add term/user invalidation where those values are embedded in the
      cached filter controls.
- [ ] Real-WordPress coverage in ticket 603 includes `/archive/unwanted-tail/`
      and Hybrid save/publish invalidation.
- [ ] `composer qa` passes.

## Out of scope

- Search-response caching and throttling (ticket 608).
- Making the endpoint slug user-configurable.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none; the final real-WordPress assertions join ticket 603
- **External:** none

## Notes / decisions log

- 2026-07-21 — `ArchiveCacheTest::test_clear_removes_all_caches()` creates only
  Blog and News settings even though `Mode::Hybrid` is a supported case.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
