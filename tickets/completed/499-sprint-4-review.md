# Ticket 499: Sprint 4 review gate (Go / No-Go)

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

The mandatory `N99` sprint review gate. Sprint 4 made the promoted archive
surface (the HTML sitemap) degrade gracefully instead of rendering nothing when
its selection query is empty, and added genuine Google-backed popularity
sourcing as an optional signal for the Blog/Top fallback. This ticket verifies
the sprint is complete before closing it out.

## Goal

A Go/No-Go decision on Sprint 4, verifying that the empty-state fallbacks work,
the Google integration is layered correctly and read-only/cache-only, and the
whole suite passes on a clean checkout.

## Acceptance criteria

- [x] Tickets 401–406 are Done and in `tickets/completed/`.
- [x] `composer qa` passes on a clean working tree — 192 tests / 467 assertions;
      PHPCS, PHPStan L9, Rector, PHParkitect, Deptrac (0 layering violations),
      and PHPMD all green.
- [x] Architectural separation holds: Google code lives in a dedicated
      `Integration` layer and `Core` does not depend on it (Deptrac green); the
      Blog fallback consumes Google only through the `PopularPostsSource`
      contract via `CompositePopularPostsSource`.
- [x] No-page-render rule verified by construction: page render reads only the
      `*CachedPopularPostsSource` classes (cache reads); every Google HTTP call
      is behind the on-demand admin-post refresh controllers, with all transports
      injected and unit-tested without a live account.
- [x] Secrets handled safely: client secret and refresh token are encrypted at
      rest and stored outside `cannyforge_archive_settings`; the secret is never
      rendered back into the form (covered by `SecretCipher` /
      `GoogleSettingsStore` tests and `SettingsViewTest`).
- [x] `tickets/overview.md` programme-status table updated; Go/No-Go recorded in
      the notes log below.

## Out of scope

- Live end-to-end verification against real Google accounts (see note below).
- New features beyond the Sprint 4 fallback/sourcing work.
- WordPress.org submission (tracked separately as future work).

## Dependencies

- **Blocks:** Sprint 5 planning (if applicable).
- **Blocked by:** 401–406.
- **External:** Google Cloud OAuth client credentials, a Search Console
  property, and a GA4 property for live verification of the Google path.

## Notes / decisions log

- 2026-06-24 — **GO.** All six build tickets (401–406) are implemented, tested,
  and in `tickets/completed/`. `composer qa` is completely green on a clean tree
  (192 tests / 467 assertions; all seven gate steps pass). The promoted surface
  now never renders empty: News falls back to the latest N (401) and Blog/Top
  falls back through a strict precedence — Google (Search Console → GA4) →
  most-commented → Jetpack → newest (402, 405, 406). The Google integration
  (404–406) is correctly layered (Deptrac green), read-only, and cache-only:
  page renders touch only the local cache, and every Google HTTP transport is
  injected and unit-tested.

- 2026-06-24 — **Live-verification caveat (not a blocker).** The Google OAuth /
  Search Console / GA4 *network* path cannot be exercised here without real
  Google Cloud credentials, a verified Search Console property, and a GA4
  property — the external dependency flagged in 404 and 406. This gate therefore
  relies on the injected-transport unit tests for the HTTP/mapping logic and on
  Deptrac/PHPStan for the wiring. The non-Google fallback tiers (401, 402) are
  fully exercisable and covered. Recommended follow-up before a production
  release: a one-time manual smoke of Connect → Refresh Search Console → Refresh
  GA4 against a live account, per `docs/GOOGLE.md`.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
