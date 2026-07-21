# Ticket 603: Real-WordPress integration test rig (replace the manual smoke checklist)

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

All 200 tests run against hand-rolled shims (`tests/wp-*-shim.php`). No real
WordPress ever executes in CI, so shim drift from real WP behaviour is
invisible until a live install breaks — which is exactly how Sprint 1 found
its two live defects. The README's "recommended smoke checklist" is a manual
browser procedure that nobody runs on every merge. The rewrite-endpoint
lifecycle (ticket 201), the admin-ajax search, and the pagination replacement
are all behaviours that only manifest inside real WordPress.

## Goal

An automated integration suite boots real WordPress in CI and executes the
current manual smoke checklist, so shim drift and WP-API misuse are caught
at merge time.

## Acceptance criteria

- [ ] `composer test:integration` boots a disposable WordPress (wp-env or
      wp-browser/Codeception — decide and record in the decisions log),
      activates the built plugin from `dist/`, and runs the suite.
- [ ] The suite covers, against real WP: `/archive/` renders seeded historic
      posts (reusing `scripts/seed-historic-content.sh` logic); the
      admin-ajax search endpoint returns correct JSON for a search + each
      filter type; a deep category archive shows the shortened pagination
      with the "View Archive" link; plugin activate → deactivate →
      reactivate leaves no rewrite-rule residue (ticket 201's guarantee).
- [ ] `.github/workflows/qa.yml` gains an `integration` job that runs the
      suite on every push/PR; a red integration run blocks merge.
- [ ] README's smoke checklist section is updated to note which items are now
      automated and which (visual/theming judgement calls) remain manual.

## Out of scope

- Browser-level JS testing of `archive-filters.js` (file a follow-up ticket
  if the integration rig makes Playwright cheap to add).
- Live Google API calls (still needs real credentials; remains a 699 item).

## Dependencies

- **Blocks:** 699
- **Blocked by:** none
- **External:** none (wp-env needs Docker, available on GitHub runners)

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
