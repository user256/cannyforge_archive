# Ticket 602: Unit tests for the Google admin controllers and settings surface

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

The untested classes cluster exactly where the risk is:
`Admin/GoogleConnectionController` (357 lines — OAuth connect/disconnect,
CSRF state transient, capability checks), `Admin/Ga4RefreshController`,
`Admin/SearchConsoleRefreshController`, `Admin/SettingsPage` (340 lines),
and the two largest files in the codebase — `Admin/ModeSettingsPanelView`
(534 lines) and `Admin/SettingsView` (480 lines) — which have partial or no
coverage. Sprint 4's review gate shipped with "live Google smoke deferred",
so right now *nothing* — neither tests nor a live check — verifies the OAuth
flow end to end.

## Goal

The OAuth controller's security decisions and the settings surface's
render/parse round-trip are pinned by unit tests.

## Acceptance criteria

- [ ] `tests/Admin/GoogleConnectionControllerTest.php` covers: connect refused
      without capability; connect refused without valid nonce; state transient
      created on connect and consumed exactly once on callback; callback with
      missing/expired/foreign state rejected; disconnect clears the token
      store; user-facing notices produced on each failure path.
- [ ] `tests/Admin/Ga4RefreshControllerTest.php` and
      `tests/Admin/SearchConsoleRefreshControllerTest.php` cover the
      capability/nonce gate and the success/failure notice paths, with the
      refresher doubled.
- [ ] `tests/Admin/SettingsPageTest.php` covers menu registration, capability
      gating, and the save round-trip (posted form → parser → repository).
- [ ] Snapshot-style render tests exist for `ModeSettingsPanelView` and the
      untested regions of `SettingsView`: current settings values appear in
      the output, all output is escaped (assert a value containing
      `<script>` renders entity-encoded).
- [ ] `composer qa` remains green; PHPMD budgets are not relaxed to make the
      views testable — if a view is too big to test, extract, don't exempt.

## Out of scope

- Live OAuth verification against real Google credentials (ticket 603 covers
  the harness; the live run stays a manual gate item in 699).
- Refactoring the views beyond what testability requires.

## Dependencies

- **Blocks:** 604
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
