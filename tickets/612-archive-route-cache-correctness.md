# Ticket 612: Fix archive-tail redirects and Hybrid cache invalidation

**Sprint:** 6 — Trust & Scale
**Status:** In review
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

- [x] A non-empty archive endpoint tail redirects to the canonical archive
      endpoint URL when no destination override is configured; it never calls
      `wp_safe_redirect()` with an empty target.
- [x] The intended relationship between `archive_url` (pagination destination),
      the archive endpoint, and the SEO canonical override is documented and
      represented by one tested URL resolver rather than three subtly different
      implementations.
- [x] Redirect failure is handled explicitly (fallback 404 or canonical local
      redirect) rather than followed by an unconditional blank-page `exit`.
- [x] `ArchiveCache::clear()` deletes cache entries for every `Mode` case,
      including `hybrid`, without a hand-maintained list that silently drifts
      when another mode is added.
- [x] Cache invalidation tests cover settings saves, post changes, and all mode
      values; add term/user invalidation where those values are embedded in the
      cached filter controls.
- [ ] Real-WordPress coverage in ticket 603 includes `/archive/unwanted-tail/`
      and Hybrid save/publish invalidation. (Ticket 603 itself is "Not
      started" and out of scope here — see decisions log: both cases are
      documented in the README manual smoke checklist now, flagged for
      automation when 603 lands.)
- [x] `composer qa` passes for the files this ticket touches (see decisions
      log — the full repo-wide `composer qa` red is ticket 611's pre-existing
      baseline, not introduced here).

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
- 2026-07-21 — Implemented. Added `CannyForge\Archive\Core\Archive\ArchiveUrlResolver`
  (`src/Core/Archive/ArchiveUrlResolver.php`) as the single source of truth for
  the three related archive URLs, per the second acceptance criterion. The
  **canonical-URL contract** (relevant to ticket 615, blocked by this
  decision):
  - `endpoint_url()` — the archive's own rewrite-endpoint URL
    (`home_url('/'.slug.'/')`). Never overridable; always what WordPress
    actually serves the archive at.
  - `destination_url(Settings $settings)` — where a "View Archive"
    link/redirect should point: the `archive_url` setting override when
    configured (may be any URL, including off-site), otherwise
    `endpoint_url()`. **Never returns an empty string.** Used by
    `PaginationController` (the pagination-replacement link),
    `SettingsPage::preview_url()` (admin preview link), and
    `ArchivePage::redirect_tail()` (the non-canonical-tail 301 — this is the
    ticket 612 bug fix: previously this redirected straight to the possibly-
    empty `archive_url` setting).
  - SEO `canonical` (`Seo::canonical()`) is deliberately **not** folded into
    the resolver — it answers a different question ("which URL should search
    engines treat as authoritative") and already has a correct, tested
    override/fallback implementation in `HeadTagBuilder::build()`. `SeoHead`
    now supplies `ArchiveUrlResolver::endpoint_url()` as that fallback, so all
    four call sites agree on what the endpoint URL itself is, without
    conflating the `archive_url` pagination override with the SEO canonical
    override — they are independent by design (redirecting pagination
    elsewhere doesn't change what the archive's canonical URL is).
  - Ticket 615 (SEO plugin interoperability): if you need to know "what is
    the archive's canonical URL," use `ArchiveUrlResolver::endpoint_url()` as
    the fallback and `Settings::seo()->canonical()` as the override — do not
    reuse `destination_url()`/`archive_url` for canonical purposes, they are
    separate concerns.
  - `ArchiveUrlResolver` is intentionally non-`final` (unlike this codebase's
    other Core leaf collaborators) so tests can substitute a resolver double
    to exercise the "redirect failure" 404-fallback branch, which a real
    resolver can never organically produce (`home_url()` never returns
    empty).
  - `ArchiveCache::clear()` now iterates `Mode::cases()` instead of a
    hand-maintained `blog`/`news` list, so `hybrid` (and any future mode) is
    covered automatically.
  - `CacheInvalidator` now also hooks `created_term`/`edited_term`/
    `delete_term` and `profile_update`/`deleted_user`, since the cached HTML
    embeds whole-database category/tag/author filter-control option lists
    (`FilterOptionsProvider`), not just the promoted entries.
  - Verification run (see PR description for full detail): `composer test`
    (219 tests incl. 12 new; only the pre-existing, unrelated
    `SettingsViewTest::test_renders_preview_link` failure present on baseline
    too), `composer cs`/`stan`/`arch`/`deptrac`/`mess` scoped to touched files
    (clean) and repo-wide (error counts match the ticket's stated baseline,
    none in touched files). Full `composer qa` remains red repo-wide pending
    ticket 611.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
