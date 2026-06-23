# Ticket 401: News empty-state fallback (latest N)

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

News mode (`NewsEntryProvider`) selects only posts published inside a rolling
recent window (`news_window_hours`, default 72). When no post falls inside that
window — a quiet news period, a site whose newest content has aged past the
window, or a freshly migrated site — the promoted archive renders **zero
entries**. The page still draws its header, filters and whole-DB filter
dropdowns (those come from `FilterOptionsProvider`, not the promoted set), so it
*looks* alive while showing an empty list and "Showing all 0 entries".

This was hit live on the local install: 1022 seeded posts, newest dated
2026-06-18, "now" = 2026-06-23, 72h window → the windowed query returned 0 and
the archive was effectively useless. An archive that can show nothing defeats
its purpose; the promoted surface should degrade gracefully to *something*
crawlable rather than nothing.

## Goal

When News mode's recent-window query returns no entries, the archive falls back
to the latest N published posts (N admin-configurable, default 50) so the
promoted surface is never empty when publishable content exists.

## Acceptance criteria

- [x] New setting `news_fallback_count` (int), default **50**, persisted,
      sanitised and clamped to 1–500 (matching `NewsEntryProvider::MAX_ENTRIES`)
      like the other count settings.
- [x] `NewsEntryProvider::provide()` returns the windowed result when it is
      non-empty; when it is empty, it returns the latest `news_fallback_count`
      published posts, newest first, regardless of date.
- [x] The fallback query-arg construction lives in a **pure**, WordPress-free
      method (`build_fallback_query_args()`) mirroring the existing
      `build_query_args()` split, and is covered by PHPUnit.
- [x] The fallback path reuses the existing post→entry mapping (`map_post`), so
      link-type toggles, noindex detection and term/author/date enrichment behave
      identically to the windowed path (same `run_query()`).
- [x] Admin settings page exposes `news_fallback_count` as a number field near
      the News-window control, with help text explaining it is the empty-window
      fallback.
- [x] Cache correctly reflects `news_fallback_count` changes: the `ArchiveCache`
      key is per-mode, and `CacheInvalidator` clears it on settings save
      (`cannyforge_archive_settings_saved`), so a UI change takes effect
      immediately. Misleading "keyed by settings" docblock corrected to state the
      real (event-driven) invalidation. (Settings-hashed keying deferred — would
      require redesigning `ArchiveCache::clear()`'s key enumeration; out of scope.)
- [x] `composer qa` passes (PHPCS, PHPStan, Rector, phparkitect, deptrac, phpmd,
      PHPUnit) — 146 tests, 353 assertions, exit 0.
- [x] Verified live at `http://127.0.0.1/archive/`: with a 72h window that matches
      0 seed posts (`window_found=0`), the archive now lists the latest 50 posts
      (newest first) instead of 0.

## Out of scope

- Changing the windowed selection logic itself (window size, ordering).
- Blog/Top empty-state fallback — that is ticket 402.
- Any popularity/analytics sourcing — recency only here.
- Surfacing a "showing fallback, not recent news" notice to end users; the
  fallback is silent (may be revisited if product wants a label).

## Dependencies

- **Blocks:** none.
- **Blocked by:** none.
- **External:** local WP install at `/var/www/html` for live verification.

## Approach

- Add `news_fallback_count` to the `Settings` value object, the repository
  defaults + sanitiser, and the admin settings schema/UI.
- In `NewsEntryProvider`: keep `build_query_args()` as-is; add a pure
  `build_fallback_query_args( Settings $settings )` that drops `date_query` and
  sets `posts_per_page = news_fallback_count`. In `provide()`, run the window
  query; if it yields 0 entries, run the fallback args through the same
  `run_query()` path.
- Unit-test: window-args unchanged; fallback args have no `date_query`, correct
  `posts_per_page`, newest-first ordering. (`provide()`'s `WP_Query` execution is
  covered by the live check, consistent with how the other providers are tested.)

## Notes / decisions log

- 2026-06-23 — Count made a **new dedicated setting** (`news_fallback_count`,
  default 50) per product owner, rather than reusing `news_window_hours` or
  hardcoding — gives explicit control over the fallback size independent of the
  window.
- 2026-06-23 — New constructor param appended **last** (`news_fallback_count`) so
  no positional caller breaks; all callers use named args anyway. Clamped in the
  ctor via min/max consts (1–500).
- 2026-06-23 — **Cache-key finding:** `ArchiveCache`'s docblock claimed the key
  was derived from settings, but it is only the mode. In practice that is fine —
  `CacheInvalidator` clears on every post save/delete and on settings save — so
  the fix was to correct the docblock, not redesign the key. The stale "0 entries"
  seen earlier was because settings had been changed via `wp eval`/`update_option`
  directly (no `cannyforge_archive_settings_saved` action), bypassing
  invalidation. Through the admin UI it self-clears.
- 2026-06-23 — **Live-verified.** Installed via `composer install:local`; set
  window=72h (empty for the 2026-06-18 seed data) + fallback=50. Windowed query
  `window_found=0`; archive rendered 50 newest-first entries (Breaking Story 1,
  Match Recap 2, …). Confirms the fallback path, not a widened window.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
