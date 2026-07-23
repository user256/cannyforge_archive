# Ticket 710: Wizard must enable Analytics Admin API and surface GA4 list failures

**Sprint:** 6 — Trust & Scale
**Status:** Completed
**Owner:** unassigned
**Estimate:** M
**Priority:** P0 — broken GA4 property picker path

---

## Context

`Ga4PropertyClient` lists properties via the **Analytics Admin** API
(`analyticsadmin.googleapis.com/.../accountSummaries`). The wizard’s “Enable
the APIs” step only tells SC+GA4 users to enable the **Analytics Data** API
(`analyticsdata.googleapis.com`), which is correct for report refresh but not
sufficient for the picker. After OAuth, `GoogleConnectionController` silently
skips caching when the list is empty, so a missing Admin API (or any list
error) lands the admin on Property with an empty GA4 dropdown and a success
“Google connected” notice.

## Goal

SC+GA4 setup instructions enable every API the wizard actually calls, and a
failed GA4 property list is surfaced as an actionable error (Connect callback
and/or Load GA4 properties).

## Acceptance criteria

- [x] App-step instructions for `sc_ga4` tell the admin to enable **both**
      Analytics Data API and Analytics Admin API, with working console library
      links for each.
- [x] When Connect runs with Analytics requested and `list_properties()` fails
      or returns empty **with** `last_error()`, the redirect notice includes that
      error (or a clear “enable Analytics Admin API / check account access”
      message) instead of only the Search Console success copy.
- [x] Manual “Load GA4 properties” already errors via `Ga4PropertyController`;
      add/adjust a test that a non-empty `last_error()` is shown.
- [x] Unit test or view test covers the Admin API enablement copy when the
      signal is `sc_ga4`, and asserts Data-only copy is insufficient.

## Out of scope

- Changing the Admin vs Data API client design (keep Admin for list, Data for
  reports).
- readme External services wording (ticket 707).

## Dependencies

- **Blocks:** live Google smoke / 699
- **Blocked by:** none
- **External:** Google Cloud API library URLs

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md).
- 2026-07-22 — Added Analytics Admin API instructions and actionable callback errors, cleared stale GA4 property cache before failed loads, and added controller/view coverage; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 710 marked done when completed.
4. Follow-ups filed, not absorbed.
