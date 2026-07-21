# Ticket 608: Performance at scale — bound the unbounded query, cache the search endpoint, rate-limit nopriv

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The plugin's pitch is large news sites — exactly the sites where three
current behaviours hurt:

1. `Core/Archive/ContentIndexProvider` has a `posts_per_page = -1` path
   (used for filter-option/total derivation) — an unbounded `WP_Query` that
   loads every matching post on a 100k-post site.
2. The whole-DB search endpoint runs an uncached `WP_Query` per request. The
   HTML-sitemap fragment is transient-cached (ticket 206) but search results
   are not, and search is the surface bots and users will hammer.
3. The endpoint is `nopriv` with a nonce but no throttle; a scripted client
   with a scraped nonce can generate arbitrary query load.

## Goal

No code path issues an unbounded query, hot search responses are served from
cache, and the public endpoint has a basic abuse ceiling — all verified on a
seeded large dataset.

## Acceptance criteria

- [ ] The `posts_per_page = -1` path is replaced: `fields => 'ids'` plus
      batched paging, or a direct bounded query — decide and record. A guard
      test asserts no `ContentQuery`/args array with `-1` reaches `WP_Query`.
- [ ] Search endpoint responses are cached (transient or object cache) keyed
      on the normalised query params, with invalidation wired into the
      existing `CacheInvalidator` on publish/update/delete.
- [ ] A lightweight per-IP throttle on the nopriv action (e.g. transient
      counter, N requests / minute, filterable via a `cannyforge_archive_*`
      hook per docs/HOOKS.md conventions) returns 429-style JSON when hit.
- [ ] Benchmark recorded in the decisions log: seed ≥ 20k posts via
      `scripts/seed-historic-content.sh --count 20000` (extend the script if
      needed), measure archive-page render and cold/warm search response
      before and after.
- [ ] `composer qa` green; new behaviour unit-tested (cache hit path, cache
      invalidation, throttle trip and reset).

## Out of scope

- Migrating admin-ajax → REST API. Worth a Sprint 7 ticket (REST gets you
  proper HTTP caching and cleaner routing), but it's a contract change for
  `archive-filters.js` and shouldn't ride along here.
- Elasticsearch/external search integrations.

## Dependencies

- **Blocks:** none
- **Blocked by:** 601 (endpoint tests exist first, so this refactor has a net)
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
