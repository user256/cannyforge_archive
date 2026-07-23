# Ticket 717: Remove the search-cache generation option on uninstall

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** S
**Priority:** P1 — uninstall correctness

---

## Context

`SearchResultCache` advances the persistent
`cannyforge_archive_search_cache_generation` option to invalidate dynamic
search transients. That key is missing from `UninstallCleaner::OPTION_KEYS`,
so plugin deletion leaves a CannyForge-owned option permanently in the site
database despite the readme's cleanup promise. Ticket 708 fixed the analogous
prefixed property-list transients; this is a distinct fixed-name option gap.

## Goal

Deleting the plugin removes its search-cache generation option as well as the
options and transients already covered by uninstall cleanup.

## Acceptance criteria

- [ ] `cannyforge_archive_search_cache_generation` is included in the
      authoritative uninstall inventory and deleted during uninstall.
- [ ] The inventory comment is updated so it names every class that writes an
      option, including `SearchResultCache`.
- [ ] Unit tests seed the generation option and prove it is absent after the
      simulated uninstall.
- [ ] A real-WordPress uninstall lifecycle test verifies no fixed CannyForge
      option remains after activation, use, and deletion; it must not rely only
      on duplicating the cleaner's own constant list.
- [ ] `composer qa` and `composer test:integration` pass.

## Out of scope

- Changing the bounded-expiry policy for result-cache or rate-limit
      transients.
- Broader database cleanup unrelated to plugin-owned keys.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit; this supplements tickets
  606 and 708 rather than reopening their completed scope.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any further orphaned key is filed as its own ticket.
