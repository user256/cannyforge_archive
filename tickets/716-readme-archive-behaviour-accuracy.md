# Ticket 716: Make the readme accurately describe archive URLs and pagination integration

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** S
**Priority:** P1 — truthful listing behaviour

---

## Context

The installation instructions say the archive has a configurable slug, but the
plugin's public endpoint is fixed at `/archive/`. The FAQ also says themes can
use a template tag, while the runtime registers only `[cannyforge_pagination]`.
These are user-visible promises that a reviewer can disprove immediately.

## Goal

The WordPress.org readme describes only archive URL and pagination extension
behaviour that the shipped plugin actually provides.

## Acceptance criteria

- [ ] The installation instructions describe the fixed `/archive/` endpoint,
      or a configurable endpoint slug is implemented with lifecycle, rewrite,
      canonical, and test coverage.
- [ ] The FAQ removes the template-tag claim, or a documented, public template
      tag is implemented and covered by tests.
- [ ] `archive_url` is described precisely as an optional View Archive link
      destination, not as an endpoint-slug setting.
- [ ] Related settings labels, screenshots, and `docs/PLAN.md` do not make a
      conflicting promise.
- [ ] Readme validation passes.

## Out of scope

- The opt-in full archive pagination feature (ticket 723).
- Changing the current archive-tail redirect policy except if required by a
      deliberately implemented configurable slug.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit. Prefer correcting the
  documentation unless a configurable slug/template tag has a clear product
  need.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Follow-up feature work is filed rather than implied by documentation.
