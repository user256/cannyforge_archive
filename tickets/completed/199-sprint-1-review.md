# Ticket 199: Sprint 1 review gate (Go / No-Go)

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

The mandatory `N99` sprint review gate. Sprint 1 is the MVP from the brief; this
ticket verifies the whole thing hangs together end-to-end before the sprint is
declared done and the next sprint is planned.

## Goal

A Go/No-Go decision on Sprint 1, backed by a full end-to-end run of the MVP.

## Acceptance criteria

- [x] Tickets 101–111 are all Done. All 11 are in `tickets/completed/`.
- [x] `composer qa` passes on a clean checkout — 93 tests / 222 assertions;
      PHPCS, PHPStan L9, Rector, PHParkitect, Deptrac (0 layering violations),
      and PHPMD all green.
- [x] Manual end-to-end smoke on a live WordPress 7.0 install (2026-06-18):
      deployed + activated cleanly (no fatal), `/archive/` resolves (HTTP 200),
      News mode rendered exactly 19 entries within the 72h window (matched a
      direct WP_Query count), Blog mode resolved a curated URL list, all 5 filter
      controls rendered, and the shortened pagination appeared on a category
      archive (linking to `/archive/`) but **not** on an author archive (Authors
      targeting off). **Two defects were found and fixed** — see log.
- [x] SEO output verified on the live install: the archive page emits
      `<meta name="robots">`, `<link rel="canonical">`, and the configured
      `<title>` (after the defect-1 render fix made `wp_head` fire).
- [x] Content selection verified: include/exclude, noindex dropping, and pinned
      ordering covered by `ContentSelectorTest` + `SelectingEntryProviderTest`
      (decorator applies to both modes via the entry list).
- [x] No-JS crawlable archive output verified on the live install: the archive
      now renders as a full themed document (`<html>/<head>/<body>` via
      `get_header()`/`get_footer()`) with the server-rendered entry list present
      independent of JavaScript.
- [x] `tickets/overview.md` programme-status table updated; Go/No-Go recorded in
      the notes log below.

## Out of scope

- Implementing any deferred feature — those become Sprint 2 tickets.

## Dependencies

- **Blocks:** Sprint 2 planning
- **Blocked by:** 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111
- **External:** a test WordPress install.

## Notes / decisions log

- 2026-06-18 — **GO (with a staging caveat).** All 11 Sprint-1 feature tickets
  (101–111) are implemented and Done, behind a fully green `composer qa`
  (93 tests / 222 assertions; PHPCS, PHPStan L9, Rector, PHParkitect, Deptrac,
  PHPMD). The layered architecture (Contracts → Core → Admin/Frontend →
  Bootstrap) has zero Deptrac violations. The MVP is build-complete.

  Caveat: three acceptance items need a live WordPress install — the end-to-end
  smoke (both modes, toggles, filters, pagination targeting), live SEO head
  verification, and HTML validation of the no-JS output. These can't run in the
  build environment (no WP runtime); all are covered indirectly by the WP-shim
  unit suite. They should be ticked off during install on staging before a
  production release. This is a deliberate, recorded deferral, not silent.

  Sprint-2 follow-ups discovered during the build (each noted in its ticket):
  - CSV file-upload UI for Blog URLs (105) — currently comma/newline list intake.
  - `paginate_links()`-based theme support for the pagination replacement (107) —
    `the_posts_pagination` themes auto-work; others use the shortcode/template tag.
  - A JS unit harness for the filter script (106) — shipped as tested-by-hand
    vanilla JS for now.
  - Optional detect/defer to Yoast/Rank Math for SEO head output (110).
  - A term-picker UI for content-selection lists (111) — names with commas
    aren't supported by the line/comma parser yet.

- 2026-06-18 — **Live smoke run on WordPress 7.0 (`/var/www/html`).** Deployed,
  activated, seeded 20 posts, ran the full smoke. **GO confirmed** — but the
  smoke earned its keep by finding two integration defects the WP-shim unit suite
  could not catch, both now **fixed and re-verified live**:

  1. **Pagination replacement never fired (107).** It hooked `the_posts_pagination`,
     which is **not a real WordPress filter** (core only applies
     `the_posts_pagination_args` and `navigation_markup_template`). Fixed:
     `PaginationController` now hooks `navigation_markup_template`, narrowed to the
     pagination block by CSS class. Re-verified: the block appears on a targeted
     category archive and is absent on an author archive.
  2. **Archive endpoint emitted a bare `<nav>` and `exit`ed before `wp_head` (103/110).**
     SEO tags never rendered and the output was not a valid document. Fixed:
     `ArchivePage::maybe_render()` renders inside the theme via
     `get_header()`/`get_footer()`; `SeoHead` now also filters
     `pre_get_document_title` so the configured title applies without a duplicate
     `<title>`. Re-verified: robots/canonical/title all present in a full document.

  Both fixes are unit-covered (97→109 tests) and `composer qa` stays green.
  Packaging issues found by the Plugin Check tool are tracked in ticket 120.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
