# Ticket 707: Refresh External services disclosure for wizard-time Analytics scope + Admin API

**Sprint:** 6 — Trust & Scale (wp.org follow-up)
**Status:** Completed
**Owner:** unassigned
**Estimate:** S
**Priority:** P1 — wp.org Guideline 6

---

## Context

The Google wizard now requests `analytics.readonly` when the admin chooses the
Search Console + GA4 signal **before** a GA4 property ID is saved, and it calls
`https://analyticsadmin.googleapis.com/v1beta/accountSummaries` to populate the
property picker. `readme.txt` still says Analytics scope is requested only when
a GA4 property ID is configured, and it never mentions the Analytics Admin API
or property-list traffic. Reviewers treat inaccurate External services
disclosures as Guideline 6 failures (see ticket 501 / prior audits).

## Goal

`readme.txt` (and `docs/GOOGLE.md`) accurately describe when Analytics scope is
requested and which Google endpoints receive traffic for property listing vs
report refresh.

## Acceptance criteria

- [x] External services documents that Analytics read-only scope is requested
      when a GA4 property ID is stored **or** the wizard’s SC+GA4 signal is
      selected at Connect time.
- [x] External services documents the Analytics Admin API account-summaries
      call used to list GA4 properties (what data is sent: access token; when:
      after Connect with Analytics scope, and on “Load GA4 properties”).
- [x] Existing Search Console / Analytics Data API / OAuth / revoke disclosures
      remain accurate and Terms/Privacy links still resolve.
- [x] FAQ “Exactly what data is sent to Google…” matches the updated section.

## Out of scope

- Changing OAuth scope policy behaviour itself (owned by 711 if reconnect is
  required).
- Wizard UI copy for enabling APIs in Google Cloud (ticket 710).

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** WordPress.org Guideline 6; Google Terms/Privacy URLs

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md).
- 2026-07-22 — Updated `readme.txt` and `docs/GOOGLE.md` for wizard-time Analytics scope and Analytics Admin API property listing; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 707 marked done when completed.
4. Follow-ups filed, not absorbed.
