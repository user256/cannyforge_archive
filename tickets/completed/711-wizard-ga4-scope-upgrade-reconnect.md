# Ticket 711: Re-authorize when upgrading from Search Console-only to SC+GA4

**Sprint:** 6 — Trust & Scale
**Status:** Completed
**Owner:** unassigned
**Estimate:** M
**Priority:** P0 — broken scope upgrade path

---

## Context

A Search Console-only Connect stores a token with only
`webmasters.readonly`. The Finish checklist “Add it” link (and the Signal step)
can switch the UI to `cf_signal=sc_ga4` and show the GA4 property picker while
Property remains reachable because the account is already connected. Loading
GA4 properties then fails: the token lacks `analytics.readonly`. Nothing forces
Reconnect with the wider scope before the picker is offered as usable.

## Goal

Choosing or upgrading to the SC+GA4 signal when the current grant does not
include Analytics requires a successful Reconnect before the GA4 picker is
treated as available.

## Acceptance criteria

- [x] If the stored connection was established without Analytics scope (no GA4
      property ID at connect time / no analytics flag in the last successful
      grant) and the current signal is `sc_ga4`, the wizard blocks or clearly
      disables GA4 load/save until the admin completes Connect/Reconnect with
      Analytics included.
- [x] Consent copy on Connect still matches `GoogleOauthScopePolicy` for the
      pending signal.
- [x] After a successful Analytics reconnect, the GA4 list can populate (given
      APIs/access from ticket 710).
- [x] Automated coverage: connected SC-only + `sc_ga4` signal does not present
      a working “Load GA4 properties” path without reauth (assert redirect,
      notice, or disabled control + copy).

## Out of scope

- Incremental Google “incremental authorization” UX beyond Reconnect.
- Changing least-privilege defaults for brand-new SC-only connects.

## Dependencies

- **Blocks:** 699 live GA4 path
- **Blocked by:** none (pairs with 710)
- **External:** Google OAuth

## Approach (optional)

Options: (a) gate Property GA4 UI behind “needs analytics reauth” derived from
settings/signal vs last granted scopes if tracked; (b) always send Connect
through when signal is `sc_ga4` and `ga4_property_id` is empty; (c) store
granted-scope flags on successful connect. Prefer the smallest change that
cannot be skipped via stepper links.

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md).
- 2026-07-22 — Persisted the Analytics-scope grant marker, gated the GA4 picker/load action for legacy or SC-only grants, and added reconnect/navigation coverage; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 711 marked done when completed.
4. Follow-ups filed, not absorbed.
