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
- [ ] Manual end-to-end smoke on a WordPress install — **deferred to staging**
      (no WP runtime available in the build environment). Logic is unit-covered
      via WP shims; the live smoke runs when the plugin is installed. See note.
- [ ] SEO output verified on a live install (`HeadTagBuilder` is unit-tested for
      all directive combinations + canonical; live verification deferred).
- [x] Content selection verified: include/exclude, noindex dropping, and pinned
      ordering covered by `ContentSelectorTest` + `SelectingEntryProviderTest`
      (decorator applies to both modes via the entry list).
- [ ] No-JS crawlable archive output verified as valid HTML — **deferred to
      staging**; the server-rendered `<nav>/<ul>` is unit-asserted and the JS is
      progressive-enhancement only.
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

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
