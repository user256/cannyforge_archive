# Project Overview — CannyForge Archive

This is the central roadmap for the plugin. The product brief lives in
[`docs/PLAN.md`](../docs/PLAN.md); the design system in
[`docs/DESIGN.md`](../docs/DESIGN.md); the developer extension points in
[`docs/HOOKS.md`](../docs/HOOKS.md).

## Programme Status

| Area | State | Outstanding / next |
|------|--------|---------------------|
| **Scaffold & quality gates** | Done | Layered `src/` (Contracts/Core/Admin/Frontend/Bootstrap), full `composer qa` gate. |
| **Sprint 1 — Settings & MVP** | Verified on live WP | 101–111 + 120 (packaging) + 121 (admin UX) done; live smoke passed; 2 defects found & fixed. 199 = GO. |
| **Sprint 2 — Hardening & fit** | Done | 201–208 implemented and tested; 299 = GO. |
| **Sprint 3 — Findability** | Done | Separated *promote* (HTML sitemap) from *find* (whole-DB search/filter). 301 done & live-verified; 399 = GO. |
| **Sprint 4 — Resilience & empty-state fallbacks** | Done | 401–406 implemented and tested: empty-state fallbacks (401/402), Google OAuth foundation (404), Search Console source (405), optional GA4 source (406). 499 = GO (qa green, 192 tests); live Google smoke deferred (needs real credentials). |
| **Sprint 5 — wp.org submission compliance** | Done | 501 completed: runtime-only packaging, direct-file guards, branded naming, and external-services readme disclosure. |
| **Sprint 6 — Trust & Scale** | In progress | 601–617 completed; 699 No-Go. Google wizard audit filed 707–713 (wp.org disclosure + functional). |

## Ticket numbering

- Each sprint owns a hundred-block: Sprint 1 = `1xx`, Sprint 2 = `2xx`, etc.
- Work tickets start at `N01`. Insert mid-sprint additions at the next free number.
- **`N99` is always the sprint review gate** — the mandatory Go/No-Go.

Use [`tickets/TICKET_TEMPLATE.md`](TICKET_TEMPLATE.md) to create new tickets.
When done, move them to [`tickets/completed/`](completed/).

## Documentation & audits

- [`docs/PLAN.md`](../docs/PLAN.md) — product brief and confirmed product decisions.
- [`docs/DESIGN.md`](../docs/DESIGN.md) — design system.
- [`docs/HOOKS.md`](../docs/HOOKS.md) — developer action/filter hooks (ticket 207).
- [`docs/GOOGLE.md`](../docs/GOOGLE.md) — Google top-content sourcing setup (Search Console + GA4, tickets 404–406).
- [`sprint-2-implementation-audit-2026-06-18.md`](sprint-2-implementation-audit-2026-06-18.md)
  — code-vs-ticket audit of 201–205.
- [`plugin-audit-2026-07-21.md`](plugin-audit-2026-07-21.md)
  — pre-Sprint-6 review gate audit (spawned 611–615).
- [`google-wizard-audit-2026-07-22.md`](google-wizard-audit-2026-07-22.md)
  — full-page Google wizard / GA4 picker audit (spawned 707–713).
- [`plugin-audit-2026-07-24.md`](plugin-audit-2026-07-24.md)
  — pre-submission wp.org + functional audit (spawned 726–731).

## Sprint 6 — Trust & Scale

Sprint 6 closes the gap between the stated quality bar and the surfaces not yet
covered by it: the public search endpoint, OAuth lifecycle, real-WordPress
behavior, release packaging, scale, accessibility, and SEO interoperability.
See the [2026-07-21 plugin audit](plugin-audit-2026-07-21.md) for evidence and
priority.

- [ ] [699 — Sprint 6 review gate (including deferred live Google smoke)](699-sprint-6-review.md)

Google wizard audit (2026-07-22) — filed against the uncommitted full-page
wizard / GA4 picker work; see
[google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md):


Pre-submission audit (2026-07-23) — filed before WordPress.org submission;
these are release-readiness fixes and block ticket 699 unless noted otherwise:


Also fixed mid-sprint, discovered independently by six of the tickets above:
[a `wp_safe_redirect` test-shim collision](completed/618-phpunit-shim-collision-silently-truncated-suite.md)
that silently truncated the whole PHPUnit run after ~8 tests while still
exiting 0 — every "`composer qa` is green" claim before this fix (this
sprint and possibly earlier ones) was unverified past that point.

## Sprint 7 — Modernisation (proposed)

Candidates filed as genuine out-of-scope discoveries during Sprint 6, not
yet scheduled. See ticket 699's own "out of scope" note for the REST-migration
and Playwright candidates also expected to land here.

- [ ] [701 — PHPUnit 10/11 and PHPStan 2.x toolchain modernisation](701-phpunit-phpstan-toolchain-modernisation.md)
- [ ] [702 — Non-text (UI component) contrast for the front-end theme's border colour](702-archive-theme-non-text-contrast.md)


Pre-submission audit (2026-07-24) — see
[plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md):

### Goal 1 — WordPress.org rejection risk


### Goal 2 — Logical / functional defects

