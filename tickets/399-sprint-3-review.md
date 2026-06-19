# Ticket 399: Sprint 3 review gate (Go / No-Go)

**Sprint:** 3 — Findability
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

The mandatory `N99` sprint review gate. Sprint 3 separated the archive's two
conflated jobs: the HTML-sitemap page *promotes* a bounded set (newest / best),
while search and filters must let users *find* the whole content database. Before
Sprint 3, the filters operated client-side over only the promoted entries, so
old / non-promoted content was undiscoverable. This ticket verifies the sprint is
complete before closing it out.

## Goal

A Go/No-Go decision on Sprint 3, verifying that promotion and findability are
cleanly separated and the whole-database navigation works end-to-end.

## Acceptance criteria

- [x] Ticket 301 is Done and in `tickets/completed/`.
- [x] `composer qa` passes on a clean checkout — 142 tests / 338 assertions;
      PHPCS, PHPStan L9, Rector, PHParkitect, Deptrac (0 layering violations), and
      PHPMD all green.
- [x] Live-verified at `http://127.0.0.1/archive/` against 1022 posts seeded
      across 2004–2026: the promoted view excludes deep old content while the
      search endpoint finds it; filters/pagination query the whole database; a bad
      nonce is rejected with HTTP 403.
- [x] `tickets/overview.md` programme-status table updated; Go/No-Go recorded in
      the notes log below.

## Out of scope

- New features beyond the findability separation delivered in Sprint 3.
- WordPress.org submission (tracked separately as future work).

## Dependencies

- **Blocks:** Sprint 4 planning (if applicable).
- **Blocked by:** 301.
- **External:** local WP install at `/var/www/html` for live verification.

## Notes / decisions log

- 2026-06-19 — **GO.** Ticket 301 is implemented, tested, and live-verified.
  `composer qa` is completely green (142 tests / 338 assertions). The archive now
  cleanly separates *promote* (the recent/curated HTML sitemap) from *find* (a
  paginated, whole-database AJAX search/filter), proven against a 22-year, 1022-post
  dataset: the 2004 post absent from the promoted view is reachable via search, a
  category filter paginates 250 posts across 13 pages, and the endpoint rejects
  invalid nonces. The deliberate AJAX decision (reversing tickets 106/205) is
  recorded in ticket 301's log.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
