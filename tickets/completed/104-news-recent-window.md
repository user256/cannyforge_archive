# Ticket 104: News mode — recent-window content query

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

In News mode the archive lists all content published within a recent window
(brief default: 72 hours). This is the News-mode entry provider that feeds the
generator (ticket 103).

## Goal

An entry provider that returns all posts published within the configured
recent window, ordered for archive display.

## Acceptance criteria

- [x] Implements the entry-provider contract from ticket 103
      (`Core\Archive\NewsEntryProvider`).
- [x] Honours `news_window_hours` from settings (default 72): the `date_query`
      cutoff is `now - window_hours` (inclusive, on `post_date_gmt`).
- [x] Query is bounded (`posts_per_page = MAX_ENTRIES`) and ordered newest
      first via `WP_Query` args (no raw SQL); only `publish` posts.
- [x] `composer test` covers the cutoff at the window boundary with a fixed
      "now" and several window sizes (the selection logic is a pure
      `build_query_args()` method, testable without a WP runtime).

## Out of scope

- Blog top-URL sourcing (ticket 105).

## Dependencies

- **Blocks:** 103 (full behaviour)
- **Blocked by:** 101
- **External:** none

## Notes / decisions log

- 2026-06-18 — Split the provider so the window selection (`build_query_args`,
  pure, takes an injected `now`) is unit-tested without WordPress; the actual
  `WP_Query` run + post→entry mapping (`run_query`/`map_post`) is the only
  WP-touching part. The boundary criterion ("just inside vs just outside") is
  expressed as cutoff-string assertions for several window sizes.
- 2026-06-18 — Added `ModeEntryProvider` (Core) so the front-end picks the
  News vs Blog provider by `Settings::mode()` as testable engine logic rather
  than branching in the composition root. Blog still uses the fixture provider
  until ticket 105 lands. Bootstrap wires `ModeEntryProvider(News, Fixture)`.
- 2026-06-18 — PHPStan (L9 + WP stubs) caught `wp_get_post_terms()` returning
  `WP_Error` and `get_the_date()` returning `false`; both are now guarded in a
  `term_names()` helper and an `is_string()` date check.
- 2026-06-18 — Capped at `MAX_ENTRIES` (500) so a wide window can't render an
  unbounded page. If a site needs more, that becomes a follow-up with paging.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
