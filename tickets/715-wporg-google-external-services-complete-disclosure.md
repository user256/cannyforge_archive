# Ticket 715: Complete the Google external-services disclosure for WordPress.org

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** M
**Priority:** P1 — WordPress.org Guideline 6

---

## Context

Ticket 707 documented the wizard-time Analytics scope and Analytics Admin API
property picker. The submission audit found that `readme.txt` still presents an
incomplete "Exactly what data is sent" inventory: the OAuth authorisation code,
redirect URI, refresh-token refresh, automatic Search Console property listing,
report request parameters, and returned/cached data are not all described. It
also links generic Google Terms instead of the Google APIs Terms and Google API
Services User Data Policy that govern this integration.

An incomplete external-services disclosure is a likely WordPress.org review
hold even where the code itself is safe.

## Goal

The readme and Google setup documentation accurately disclose every optional
Google API call, its trigger, the data sent and received, local retention, and
the applicable Google policies.

## Acceptance criteria

- [ ] `readme.txt` inventories OAuth token exchange, refresh, and revocation;
      Search Console property listing and report queries; GA4 Admin property
      listing and Data API report queries, including their hostnames.
- [ ] For each call, the disclosure states the user action or automated trigger,
      request data (including OAuth code/redirect URI/tokens and report
      parameters), response data, and whether/how long it is cached locally.
- [ ] The automatic Search Console property-list call immediately after a
      relevant successful connection is explicitly documented.
- [ ] Links resolve to Google APIs Terms, Google API Services User Data Policy,
      and Google Privacy Policy; GA4 terms are linked where applicable.
- [ ] The FAQ is consistent with the External services section and does not
      claim an exhaustive disclosure while omitting traffic.
- [ ] Readme validation and the WordPress Plugin Check pass; focused tests or
      documentation assertions cover the new wording where practical.

## Out of scope

- Changing OAuth scopes, endpoints, token encryption, or Google account UI.
- Adding a new third-party service.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** Google API policy URLs; WordPress.org Guideline 6

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit. Extends completed ticket
  707; it is not a duplicate because it covers the remaining endpoint-level
  data-flow and policy-link gaps.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any newly discovered service or retention behaviour is filed separately.
