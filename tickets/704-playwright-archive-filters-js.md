# Ticket 704: Browser-level (Playwright) tests for archive-filters.js

**Sprint:** 7 — Modernisation (proposed)
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Ticket 603 built a real-WordPress integration rig (wp-env, disposable
WordPress, `composer test:integration`) that boots a real, addressable
WordPress instance in CI. That instance is exactly what a browser-automation
suite needs a target to point at — the hard, previously-missing part (a real
WP to test against) now exists. `assets/js/archive-filters.js` — the
client-side search-box and category/tag/month/author filtering behaviour on
the default `/archive/` view — was explicitly out of scope for ticket 603 and
remains a manual smoke-check item in README.md (typing in the search box,
changing filters, confirming the visible list narrows without a page reload).

## Goal

The client-side filtering behaviour in `assets/js/archive-filters.js` is
covered by automated browser tests running against the same disposable
wp-env instance the PHP integration suite already boots, so the two manual
"type in the search box" / "change filters" smoke-checklist items in
README.md can be marked automated.

## Acceptance criteria

- [ ] Playwright (or `@wordpress/env` + `@wordpress/e2e-test-utils-playwright`,
      whichever proves lower-friction — decide and record why) is added as a
      dev dependency and runs against the wp-env instance booted by
      `scripts/run-integration-tests.sh`.
- [ ] Automated coverage: typing in the archive search box narrows the
      visible list client-side without a page reload; changing each filter
      (category, tag, month, author) narrows the list; clearing
      filters/search restores the default view.
- [ ] `composer test:integration` (or a clearly-named sibling script/CI job)
      runs the Playwright suite alongside the existing PHP integration suite,
      and a red run blocks merge.
- [ ] README.md's "Still manual" smoke-checklist section is updated to move
      the now-automated items out of the manual list.

## Out of scope

- Testing the whole-database admin-ajax search endpoint's server-side JSON
  contract — already covered by `tests/WpIntegration/AdminAjaxSearchTest.php`
  (ticket 603).
- Visual/theming regression testing (screenshot diffing) — a separate concern
  from functional JS behaviour.

## Dependencies

- **Blocks:** none
- **Blocked by:** 603 (needs the wp-env rig this ticket reuses)
- **External:** none

## Approach (optional)

Reuse `scripts/run-integration-tests.sh`'s wp-env boot/seed steps (extract the
setup into a shared step if the PHP and Playwright suites both need it) rather
than duplicating the wp-env lifecycle in a second script.

## Notes / decisions log

- 2026-07-21 — Filed from ticket 603's implementation: the integration rig
  makes this cheap now that a real, addressable WordPress instance boots in
  CI; only the browser-automation layer itself is missing.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
