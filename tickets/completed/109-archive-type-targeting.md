# Ticket 109: Archive-type targeting controls

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The pagination replacement (ticket 107) must not blanket every paginated view.
The administrator decides which WordPress archive types it applies to. This
ticket adds those targeting toggles to the settings model and admin UI; ticket
107 consumes them.

## Goal

Per-archive-type toggles that scope where the pagination replacement applies,
with the confirmed default recommendation.

## Acceptance criteria

- [x] Settings gain a targeting group with four toggles: Categories, Tags,
      Authors, Date Archives (`Contracts\Settings\Targeting`).
- [x] Defaults match the confirmed recommendation: Categories **on**, Tags
      **on**, Authors **off**, Date Archives **off**.
- [x] The admin settings page renders the four toggles (per the brief's
      checkbox style) and persists them via the settings model.
- [x] A small, pure helper answers "does the replacement apply to *this* request
      type?" — `Core\Pagination\TargetingPredicate::applies(Targeting,
      ArchiveContext)`, with `ArchiveContext::from_wp()` building the context
      from the live conditional tags. Ticket 107 has one testable decision point.
- [x] `composer test` covers the helper for each archive type in both enabled
      and disabled states (plus defaults and a non-archive request); `composer qa`
      passes.

## Out of scope

- The pagination rendering itself (ticket 107) — this ticket only decides
  *whether* it applies.
- Targeting individual taxonomies beyond the four named types.

## Dependencies

- **Blocks:** 107 (needs the targeting decision)
- **Blocked by:** 101 (settings model), 102 (admin UI to surface the toggles)
- **External:** none

## Approach (optional)

Add a `Targeting` value object to `Contracts\Settings` alongside `LinkTypes` /
`Filters`, and a small `Core` predicate that maps a WordPress query-context flag
set (is_category / is_tag / is_author / is_date) to a yes/no, injectable so the
predicate is unit-testable without a WP runtime.

## Notes / decisions log

- 2026-06-18 — Created from the confirmed product decisions (archive-type
  targeting). Default recommendation recorded above.
- 2026-06-18 — Implemented. `Targeting` VO in `Contracts\Settings` (mirrors
  `LinkTypes`/`Filters`), wired into `Settings` + admin view ("Pagination
  Targeting" section) + form parser. Decision logic is the pure
  `Core\Pagination\TargetingPredicate` over a `Core\Pagination\ArchiveContext`
  (decoupled from `is_category`/`is_tag`/`is_author`/`is_date` so it's testable
  without WP). Ticket 107 consumes the predicate.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
