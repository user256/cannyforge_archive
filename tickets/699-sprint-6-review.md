# Ticket 699: Sprint 6 review gate — Trust & Scale (Go/No-Go)

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Mandatory sprint gate per the ticket-numbering convention. Sprint 6 exists to
close the gap between the project's excellent *stated* quality bar and the
places the bar wasn't actually applied: the untested public endpoint and OAuth
surface, the toothless mutation gate, the shim-only test strategy, the cipher
weaknesses, and lifecycle/compat claims nothing verifies. This gate also
finally clears the debt carried since 499: the deferred live Google smoke.

## Goal

A Go/No-Go decision confirming every Sprint 6 guarantee holds on a real
WordPress install, including the live Google flow deferred at 499.

## Acceptance criteria

- [ ] `composer qa` green on the full CI matrix (607), with Infection gating
      at the recorded minMsi (604), the integration suite green (603), and the
      release/package baseline restored (611).
- [ ] **Live Google smoke executed with real credentials** (carried from
      499): connect, Search Console refresh, GA4 refresh, disconnect, and —
      new — the 605 recovery path after a deliberate salt rotation.
- [ ] Uninstall verified on a live site: no plugin rows remain, Google grant
      revoked (606).
- [ ] Scale benchmark from 608 reviewed; archive render and warm search
      response meet the numbers recorded in that ticket.
- [ ] Accessibility spot-check: keyboard-only walkthrough of search → filter
      → paginate; screen reader announces result updates (609).
- [ ] `dist/` rebuilt and re-audited against WordPressAudit.md, including the
      new `uninstall.php`, `.pot`, and listing assets (610).
- [ ] Archive tail/caching checks (612), admin UI behavior (613), least-privilege
      OAuth/revocation (614), and SEO-provider interoperability (615) are
      verified in their declared matrices.
- [ ] Defects found during review filed as tickets, not silently fixed.
- [ ] Go/No-Go recorded below with evidence links.

## Out of scope

- New feature work discovered during review — file it for Sprint 7
  (candidates already identified: REST migration of the search endpoint,
  PHPUnit 10 / PHPStan 2 toolchain bump, user-supplied encryption key
  constant, Playwright coverage of archive-filters.js).

## Dependencies

- **Blocks:** Sprint 7
- **Blocked by:** 601–615
- **External:** Real Google Cloud OAuth credentials for the live smoke

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Go/No-Go decision recorded in this ticket and in `tickets/overview.md`.
3. Completed tickets moved to `tickets/completed/` (or run
   `python process_tickets.py --apply`).
4. Follow-up work filed as Sprint 7 tickets.

---

## Overview snippet (paste into tickets/overview.md)

```markdown
## Sprint 6 — Trust & Scale

The quality bar is excellent where it was applied — Sprint 6 applies it to
the places it wasn't: the untested public search endpoint and OAuth surface,
the never-armed mutation gate, the shim-only test strategy, unauthenticated
secret encryption, missing uninstall cleanup, unverified PHP/WP compatibility
claims, and the front-end's accessibility and large-site performance.

- [ ] [601 — Unit tests for ArchiveSearchEndpoint](601-archive-search-endpoint-tests.md)
- [ ] [602 — Unit tests for Google admin controllers & settings surface](602-google-admin-controller-tests.md)
- [ ] [603 — Real-WordPress integration test rig](603-real-wordpress-integration-rig.md)
- [ ] [604 — Infection minMsi threshold as a merge gate](604-infection-msi-gate.md)
- [ ] [605 — SecretCipher hardening (AEAD, fail-loud, key rotation)](605-secretcipher-hardening.md)
- [ ] [606 — uninstall.php: options, transients, token revocation](606-uninstall-cleanup.md)
- [ ] [607 — CI version matrix + repo hygiene](607-ci-matrix-repo-hygiene.md)
- [ ] [608 — Performance at scale (bounded queries, caching, throttle)](608-performance-at-scale.md)
- [ ] [609 — Archive accessibility pass (WCAG 2.2 AA)](609-archive-accessibility.md)
- [ ] [610 — i18n completeness + wp.org listing assets](610-i18n-wporg-listing.md)
- [x] [611 — Restore release gate + runtime-only package](completed/611-release-branch-stabilisation.md) (follow-up: 616)
- [x] [612 — Archive-tail redirects + Hybrid cache invalidation](completed/612-archive-route-cache-correctness.md) (follow-up: 617)
- [x] [613 — Admin settings UI integrity + accessibility](completed/613-admin-settings-ui-integrity.md)
- [x] [614 — Google OAuth least privilege + revocation](completed/614-google-oauth-least-privilege-lifecycle.md)
- [x] [615 — SEO plugin interoperability + canonical ownership](completed/615-seo-plugin-interoperability.md)
- [ ] [616 — Restore PHP 8.1 Composer-lock compatibility](616-php81-composer-lock-compatibility.md)
- [ ] [617 — Handle rejected archive-tail safe redirects](617-archive-tail-safe-redirect-fallback.md)
- [ ] [699 — Sprint 6 review gate (incl. deferred live Google smoke)](699-sprint-6-review.md)
```
