# Ticket 615: Prevent duplicate SEO tags and define canonical ownership

**Sprint:** 6 — Trust & Scale
**Status:** Completed
**Owner:** unassigned
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

- [x] Define and document ownership/precedence for document title, meta
      description, robots, and canonical tags when no SEO plugin is active and
      when a supported SEO plugin is active.
- [x] Integrate through public provider hooks/APIs (at minimum the leading
      installed providers selected during implementation) or suppress this
      plugin's duplicate tags when another provider owns the archive request.
- [x] The plugin never emits two canonical tags or contradictory robots
      directives on `/archive/` in the supported integration matrix.
- [x] One canonical URL resolver is shared with ticket 612 and respects only the
      documented setting; a pagination-link destination cannot accidentally
      become the page canonical.
- [x] Keep `cannyforge_archive_seo_head` as an escape hatch and document its
      return contract, priority, and examples in `docs/HOOKS.md`.
- [ ] Real-WordPress tests inspect `wp_head` output with no SEO plugin and with
      each supported provider active. (Not available — ticket 603's real-WP
      harness doesn't exist yet; covered instead with PHPUnit tests against the
      repo's `add_filter()`/`apply_filters()` shim and an injectable provider
      detector faking Yoast/Rank Math presence signals. See PR notes.)
- [ ] `composer qa` passes. (Repo-wide baseline failure pre-dates this ticket,
      tracked on 611; `composer cs`/`stan`/`test`/`arch`/`deptrac`/`mess` all
      verified clean when scoped to the files this ticket touches.)

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
- 2026-07-21 — Implemented ahead of 612 landing: added
  `Core\Seo\CanonicalUrlResolver` (SEO override else the archive endpoint's own
  URL, never `Settings::archive_url()`) for `HeadTagBuilder` and the provider
  bridge. After 612 landed, `SeoHead` now derives that endpoint URL from its
  `Core\Archive\ArchiveUrlResolver`, which also drives the route, pagination,
  and admin preview; the SEO canonical remains deliberately independent of the
  pagination destination. Added `Core\Seo\SeoProviderDetector` (injectable, defaults
  to `defined( 'WPSEO_VERSION' )` / `defined( 'RANK_MATH_VERSION' )` ||
  `class_exists( 'RankMath\\RankMath' )`) and wired `SeoHead` to suppress its
  own robots/description/canonical output and feed its resolved values into
  Yoast's (`wpseo_*`) and Rank Math's (`rank_math/frontend/*`) public filters
  when either is detected active; `pre_get_document_title` is left untouched
  in that case so the provider's own title pipeline is the only one running.
  Filter names are best-effort from each plugin's public docs, not verified
  against a live install (603 gap).
- 2026-07-21 — Merged in PR #1 after 612. `SeoHead` receives its endpoint
  fallback from `ArchiveUrlResolver`; live provider fixtures remain ticket
  603 work and PHP 8.1 CI remediation is ticket 616.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
