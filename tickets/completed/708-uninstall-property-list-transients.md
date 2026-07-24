# Ticket 708: Uninstall must remove user-scoped Google property-list transients

**Sprint:** 6 — Trust & Scale (wp.org follow-up)
**Status:** Completed
**Owner:** unassigned
**Estimate:** S
**Priority:** P1 — wp.org / incomplete cleanup vs readme claim

---

## Context

`SearchConsolePropertyStore` and the new `Ga4PropertyStore` persist lists under
`cannyforge_archive_sc_properties_{user_id}` and
`cannyforge_archive_ga4_properties_{user_id}`. `uninstall.php` only deletes
fixed-name HTML-cache transients plus the OAuth CSRF prefix
(`cannyforge_archive_google_oauth_`). The readme claims deleting the plugin
“permanently removes every option, cache, and transient the plugin created.”
Leftover rows after delete are both a product lie and a review risk next to
ticket 606.

## Goal

Uninstall removes every Search Console and GA4 property-list transient for the
site (all user suffixes), matching the readme cleanup claim.

## Acceptance criteria

- [x] Per-site uninstall deletes all `_transient_cannyforge_archive_sc_properties_%`
      and `_transient_cannyforge_archive_ga4_properties_%` rows (and their
      `_transient_timeout_` twins), using the same prepared `$wpdb` pattern as
      the OAuth state cleanup.
- [x] Unit/uninstall tests assert those prefixes are gone after a simulated
      uninstall (extend `UninstallScriptTest` / cleaner coverage).
- [x] `UninstallCleaner` docs / inventory comment mention the prefixed property
      stores or point at the `$wpdb` helper that deletes them.
- [x] readme cleanup wording remains true after the fix (no overclaim).

## Out of scope

- Prefixed search-result / throttle transients (pre-existing; file a follow-up
  if the audit wants them swept in the same pass).
- Changing property-store TTLs or key shapes for the live feature.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none (builds on completed 606)
- **External:** none

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md). GA4 store is new in the wizard diff; SC store was already missing from uninstall.
- 2026-07-22 — Added prepared wildcard cleanup for SC/GA4 property and timeout transients, documented bounded-TTL result/throttle transients accurately, and added uninstall coverage; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 708 marked done when completed.
4. Follow-ups filed, not absorbed.
