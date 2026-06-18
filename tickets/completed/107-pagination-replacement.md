# Ticket 107: Pagination replacement with "View Archive" link

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The other half of the brief: replace WordPress's default taxonomy pagination
(`Previous « 1 2 3 4 5 … » Next`) with a shorter, fixed sequence that links out
to the archive (`1 2 3 4 5 6 7 8 9  View Archive →`). This is what conserves
crawl budget and routes crawlers to the single rich archive page (ticket 103).

## Goal

A pagination renderer that shows at most the configured number of page links,
then a "View Archive" link instead of the long default tail.

## Acceptance criteria

- [x] On supported archive listings, the default paginated tail is replaced
      with a sequence capped at `pagination_limit` (default 1) followed by a
      "View Archive" link (`Core\Pagination\PaginationRenderer`).
- [x] The "View Archive" link destination is configurable via a new
      `archive_url` setting; empty falls back to the ticket-103 archive endpoint
      (`home_url('/'.slug.'/')`).
- [x] The replacement only applies to the archive types enabled in the
      targeting controls (ticket 109) — `PaginationController` consults
      `TargetingPredicate` and returns the theme's markup unchanged otherwise, so
      it never hijacks unrelated views or double-renders.
- [x] Beyond the limit, deep page links are not emitted (the crawl-budget goal).
- [x] `composer test` covers link-count for several `pagination_limit` values,
      the archive link target (including a configured override), and that an
      untargeted request is left untouched. Exposed as the
      `[cannyforge_pagination]` shortcode + the controller's `shortcode()`
      template-tag entry point for explicit placement.

## Out of scope

- The archive page itself (ticket 103).
- Which archive types are eligible — that selection is ticket 109; this ticket
  only consumes it.
- Theme-specific styling beyond ticket 108.

## Dependencies

- **Blocks:** none
- **Blocked by:** 101 (limit setting), 103 (archive URL to link to),
  109 (archive-type targeting — which listings the replacement applies to)
- **External:** none

## Notes / decisions log

- 2026-06-18 — Confirmed product decisions: pagination limit stays default 1;
  the archive-link destination is configurable; the replacement is restricted
  to the archive types selected in the targeting controls (ticket 109).
- 2026-06-18 — Implemented. Pure `Core\Pagination\PaginationRenderer` (cap +
  markup, fully unit-tested) and thin `Frontend\PaginationController` that hooks
  `the_posts_pagination`, consults the ticket-109 predicate, and also registers
  the `[cannyforge_pagination]` shortcode / template tag (per the confirmed hook
  decision: filter core nav markup *and* expose explicit placement). Added the
  configurable `archive_url` setting (admin field + parser). Follow-up worth a
  ticket: themes that render pagination by calling `paginate_links()` directly
  rather than `the_posts_pagination()` won't be auto-filtered — they can use the
  shortcode/template tag, or a future ticket can add a `paginate_links` filter.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
