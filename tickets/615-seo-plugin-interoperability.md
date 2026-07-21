# Ticket 615: Prevent duplicate SEO tags and define canonical ownership

**Sprint:** 6 — Trust & Scale
**Status:** In progress
**Owner:** background-agent
**Estimate:** M
**Priority:** P1 — SEO correctness

---

## Context

`SeoHead` emits robots, canonical, and description tags by default and relies on
a CannyForge-specific filter for third parties to suppress them. On the news and
publisher sites this plugin targets, an SEO plugin is common; without explicit
integration both plugins can emit competing canonical/robots/description tags.
The archive endpoint canonical, the optional pagination destination, and the
SEO canonical override also currently have separate resolution paths (see 612).

## Goal

The archive produces one authoritative set of SEO directives and interoperates
predictably with established WordPress SEO providers.

## Acceptance criteria

- [ ] Define and document ownership/precedence for document title, meta
      description, robots, and canonical tags when no SEO plugin is active and
      when a supported SEO plugin is active.
- [ ] Integrate through public provider hooks/APIs (at minimum the leading
      installed providers selected during implementation) or suppress this
      plugin's duplicate tags when another provider owns the archive request.
- [ ] The plugin never emits two canonical tags or contradictory robots
      directives on `/archive/` in the supported integration matrix.
- [ ] One canonical URL resolver is shared with ticket 612 and respects only the
      documented setting; a pagination-link destination cannot accidentally
      become the page canonical.
- [ ] Keep `cannyforge_archive_seo_head` as an escape hatch and document its
      return contract, priority, and examples in `docs/HOOKS.md`.
- [ ] Real-WordPress tests inspect `wp_head` output with no SEO plugin and with
      each supported provider active.
- [ ] `composer qa` passes.

## Out of scope

- Adding Open Graph, Twitter Card, or schema.org output.
- General SEO-plugin features unrelated to the archive endpoint.

## Dependencies

- **Blocks:** 699
- **Blocked by:** 612 canonical-contract decision; integration harness from 603
- **External:** supported SEO plugin test fixtures

## Notes / decisions log

- 2026-07-21 — The audit found no provider detection or provider-specific hook;
  the current `SeoHead` always emits its fragment on the archive query var.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
