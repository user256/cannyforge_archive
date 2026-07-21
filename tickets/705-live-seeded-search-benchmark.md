# Ticket 705: Run the real 20k-post live search/render benchmark from ticket 608

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Ticket 608 (performance at scale) bounded the unbounded `WP_Query`, added a
search response cache, and added a per-IP throttle to the archive search
endpoint. Its acceptance criteria called for a benchmark on a WordPress
instance seeded with >= 20k posts via `scripts/seed-historic-content.sh
--count 20000`, measuring archive-page render and cold/warm search response
before and after.

That measurement was not performed. The only live WordPress instance
reachable from the ticket 608 worktree (`/var/www/html`) is shared, ambient
sandbox infrastructure — other agents were actively working in parallel
worktrees against the same filesystem path at the time, so seeding it with
20k posts or installing an in-progress branch's build into it risked
mutating a resource without exclusive access to it. Ticket 608 substituted a
standalone, no-WordPress query-building-logic timing/scaling comparison
(`scripts/benchmark-bounded-query.php`) instead, which demonstrates the
PHP-side materialisation cost difference (old: O(n) array of matching post
IDs; new: O(1)) but not the actual MySQL query cost, page-render time, or
real cold/warm HTTP response timings the ticket asked for.

## Goal

The bounded-query, cache, and throttle changes from ticket 608 are measured
end-to-end on a real, seeded (>= 20k post) WordPress instance, with numbers
recorded against a dedicated or exclusively-held instance — not a shared
sandbox another agent could be mutating concurrently.

## Acceptance criteria

- [ ] A WordPress instance with exclusive/dedicated access (ideally ticket
      603's real-WordPress integration rig, once merged, rather than an
      ad hoc shared sandbox instance) is seeded with `scripts/seed-historic-content.sh
      --count 20000`.
- [ ] Archive page render time is measured before/after the ticket 608
      changes (or, if only the "after" state is reachable, at minimum a
      cold vs. warm `ArchiveCache` comparison on the seeded dataset).
- [ ] Search endpoint response time is measured cold (cache miss) and warm
      (cache hit) on the seeded dataset, via a real HTTP request to the
      `cannyforge_archive_search` admin-ajax action.
- [ ] Numbers are recorded in this ticket's decisions log, alongside a
      comparison against the `scripts/benchmark-bounded-query.php` estimate
      from ticket 608 (does the real query-cost saving roughly track the
      PHP-side materialisation saving, or is it dominated by something
      else — e.g. `found_posts`'s own index scan cost?).

## Out of scope

- Any further code changes to the bounded query, cache, or throttle — this
  ticket is purely a measurement follow-up. If the numbers reveal a real
  problem, file a new ticket for the fix.

## Dependencies

- **Blocks:** none
- **Blocked by:** none strictly, but likely much easier once ticket 603
  (real-WordPress integration rig) lands, since it should provide exactly
  the kind of dedicated/exclusive WordPress instance this ticket needs
  instead of relying on a shared ad hoc sandbox.
- **External:** a WordPress instance not shared with concurrently-running
  agents/tasks.

## Notes / decisions log

- 2026-07-21 — Filed from ticket 608, where the live benchmark step was
  substituted with a standalone timing/scaling comparison for the reasons
  above.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
