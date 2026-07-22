# Ticket 699: Sprint 6 review gate — Trust & Scale (Go/No-Go)

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** Codex
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
- **Blocked by:** 601–617
- **External:** Real Google Cloud OAuth credentials for the live smoke

## Notes / decisions log

- {date} — {decision or finding}
- 2026-07-22 — **Repository audit: local gates and package evidence.** At
  commit `1c7f9c4`, `composer validate --strict --no-check-publish`,
  `composer qa`, and `composer review:sim` passed. The Composer suite ran all
  366 PHPUnit tests with 1,057 assertions; `npm test -- --runInBand --no-cache`
  passed 49 tests. `git diff --check` was clean. `composer dist` rebuilt the
  runtime tree and ZIP; the package audit found 115 ZIP entries, 82 PHP files
  with clean `php -l` results, `uninstall.php`, one `.pot`, no tests/vendor/
  node_modules/tickets/development files, and all three `.wordpress-org/`
  screenshots present. These results are evidence for the local portions of
  604, 607, and 611, but not proof of the full CI matrix or the live portions
  of this gate.
- 2026-07-22 — **Integration audit: not yet green.** `composer
  test:integration` was attempted from a clean tree. The first attempt found
  the disposable rig's stale Docker dependency; after the isolated rig was
  recreated, a second attempt exposed a bind-mount ordering defect: the
  command's `@dist` step recreates `dist/cannyforge-archive` while wp-env is
  already running, leaving the mounted plugin directory empty and causing
  activation to fail. The lower-level `bash scripts/run-integration-tests.sh`
  run, with the existing dist mounted before startup, reached real WordPress
  installation, plugin activation, classic-theme selection, and permalink
  flushing. It did not produce a test result because another isolated seed
  process was already using the same wp-env rig; that process was left alone.
  No shared port-80 site was touched by these checks.
- 2026-07-22 — **Decision: No-Go pending gate evidence.** Local QA and package
  checks are green, but the full CI matrix, completed real-WordPress
  integration result, live Google smoke (including salt-rotation recovery),
  live uninstall/grant revocation, real-site scale benchmark, manual
  keyboard/screen-reader check, and final complete WordPressAudit.md review
  remain unverified. The integration runner ordering defect also needs a
  follow-up ticket before the review gate can be closed.
- 2026-07-22 — **Integration rerun: green.** After ticket 706's wp-env
  preflight stop and Composer timeout allowance, a clean `composer
  test:integration` run seeded 48 posts on Twenty Seventeen and passed all 11
  real-WordPress tests with 97 assertions. The disposable rig was stopped by
  runner cleanup. The local Infection rerun also passed its armed gate:
  3,167 mutants, MSI 50%, Covered Code MSI 57%, thresholds 48%/55%, with no
  errors, syntax failures, or timeouts.

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

- [x] [601 — Unit tests for ArchiveSearchEndpoint](completed/601-archive-search-endpoint-tests.md)
- [x] [602 — Unit tests for Google admin controllers & settings surface](completed/602-google-admin-controller-tests.md)
- [x] [603 — Real-WordPress integration test rig](completed/603-real-wordpress-integration-rig.md) (follow-up: 704)
- [x] [604 — Infection minMsi threshold as a merge gate](completed/604-infection-msi-gate.md)
- [x] [605 — SecretCipher hardening (AEAD, fail-loud, key rotation)](completed/605-secretcipher-hardening.md)
- [x] [606 — uninstall.php: options, transients, token revocation](completed/606-uninstall-cleanup.md)
- [x] [607 — CI version matrix + repo hygiene](completed/607-ci-matrix-repo-hygiene.md)
- [x] [608 — Performance at scale (bounded queries, caching, throttle)](completed/608-performance-at-scale.md)
- [x] [609 — Archive accessibility pass (WCAG 2.2 AA)](completed/609-archive-accessibility.md) (follow-up: 702)
- [x] [610 — i18n completeness + wp.org listing assets](completed/610-i18n-wporg-listing.md) (follow-up: 703)
- [x] [611 — Restore release gate + runtime-only package](completed/611-release-branch-stabilisation.md) (follow-up: 616)
- [x] [612 — Archive-tail redirects + Hybrid cache invalidation](completed/612-archive-route-cache-correctness.md) (follow-up: 617)
- [x] [613 — Admin settings UI integrity + accessibility](completed/613-admin-settings-ui-integrity.md)
- [x] [614 — Google OAuth least privilege + revocation](completed/614-google-oauth-least-privilege-lifecycle.md)
- [x] [615 — SEO plugin interoperability + canonical ownership](completed/615-seo-plugin-interoperability.md)
- [x] [616 — Restore PHP 8.1 Composer-lock compatibility](completed/616-php81-composer-lock-compatibility.md)
- [x] [617 — Handle rejected archive-tail safe redirects](completed/617-archive-tail-safe-redirect-fallback.md)
- [ ] [699 — Sprint 6 review gate (incl. deferred live Google smoke)](699-sprint-6-review.md)
```
