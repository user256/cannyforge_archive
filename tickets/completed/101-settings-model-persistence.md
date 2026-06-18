# Ticket 101: Settings model & persistence

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Every other Sprint 1 feature (admin UI, generator, pagination, filters) reads
from one source of truth: the plugin's settings. Without a typed, validated
settings model the rest of the sprint has nothing to bind to. This ticket
establishes that model and its WordPress persistence first.

## Goal

A typed, validated settings object that round-trips through the WordPress
options API with sane defaults from the brief.

## Acceptance criteria

- [x] A `Settings` value object holds: `mode` (`blog`|`news`),
      `pagination_limit` (int, default `1`), the link-type toggles
      (title default `true`, description/featured-image default `false`), the
      five filter toggles (search/category/tag/month_year/author), the
      `news_window_hours` (int, default `72`), `blog_max_urls` (int, default
      `100`), and `blog_urls` (string[]). Implemented as framework-free value
      objects (`Settings`, `LinkTypes`, `Filters`, `Mode`) behind the
      `SettingsRepositoryInterface` contract.
- [x] Defaults match the brief exactly (see [`docs/PLAN.md`](../docs/PLAN.md)).
- [x] Persistence uses a single prefixed option key
      (`cannyforge_archive_settings`) via `OptionsSettingsRepository`, sanitising
      on save and coercing/clamping on load.
- [x] `composer test` covers default construction, round-trip, coercion, and
      clamping of out-of-range values (16 assertions across the model + repo).
- [x] `composer qa` passes (PHPCS, PHPStan L9, Rector, PHParkitect, Deptrac,
      PHPMD, PHPUnit).

## Out of scope

- The admin UI that edits these settings (ticket 102).
- Snowflake / Adobe URL sourcing (final version, not MVP).

## Dependencies

- **Blocks:** 102, 103, 104, 105, 106, 107
- **Blocked by:** none
- **External:** none

## Approach (optional)

Keep the value object immutable; do sanitisation in a mapper at the WP-options
boundary so the object itself stays framework-free and unit-testable without a
WP runtime.

## Notes / decisions log

- 2026-06-18 — The value objects (`Settings`, `LinkTypes`, `Filters`, `Mode`)
  live in `Contracts\Settings`, not `Core`. They are the shared data type the
  `SettingsRepositoryInterface` returns, and Deptrac correctly forbids Contracts
  (the dependency sink) from depending on Core. Only the WP-backed
  `OptionsSettingsRepository` lives in `Core`. The approach note below
  (immutable VO + mapper at the WP boundary) held; the layer it lands in changed.
- 2026-06-18 — Tests run without a WordPress runtime via a small in-memory
  options shim (`tests/wp-options-shim.php` + `tests/OptionStore.php`), wired
  through `tests/bootstrap.php`.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
