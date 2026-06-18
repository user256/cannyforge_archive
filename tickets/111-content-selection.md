# Ticket 111: Content selection — include/exclude, noindex, pinned priority

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

Both News and Blog modes need finer control over *which* content reaches the
archive and in what order. This ticket adds inclusion/exclusion rules, a way to
drop noindex content, and pinned URLs that display first — applied to the entry
set before rendering (ticket 103).

## Goal

A content-selection stage that filters and orders the provided entries by the
configured include/exclude rules, noindex handling, and pinned priority — for
both modes where relevant.

## Acceptance criteria

- [ ] Settings gain content-selection rules: include categories, include tags,
      exclude categories, exclude tags, an "exclude noindex content" toggle, and
      a pinned-URL list (displayed first, in order).
- [ ] The admin settings page renders these and persists them via the settings
      model.
- [ ] A pure `Core` selector transforms an `ArchiveEntry[]` into the final set:
      apply include filters (an entry must match at least one included term when
      includes are set), then exclude filters (drop on any excluded term), drop
      noindex entries when the toggle is on, then move pinned URLs to the front
      in their configured order.
- [ ] The selector runs for both News and Blog providers (it operates on the
      entry list, not the source), so the rules apply where relevant.
- [ ] `composer test` covers include-only, exclude-only, include+exclude
      precedence, noindex dropping, and pinned ordering; `composer qa` passes.

## Out of scope

- Analytics/popularity-based ordering (explicitly out of scope plugin-wide).
- The archive page's own index/noindex (ticket 110) — this ticket is about
  *which entries* are included, not the archive page's robots meta.
- Sourcing the entries (tickets 104 / 105) — this filters what they produce.

## Dependencies

- **Blocks:** none
- **Blocked by:** 101 (settings model), 102 (admin UI), 103 (entry shape +
  renderer), 104 + 105 (the providers whose output it filters)
- **External:** none

## Approach (optional)

`ArchiveEntry` already carries categories/tags; add a noindex flag to it (and to
the provider mappers) so the selector can act purely on the entry list. Wire the
selector between the `ModeEntryProvider` and the renderer in Bootstrap, keeping
the selector itself a framework-free, unit-tested `Core` transform.

## Notes / decisions log

- 2026-06-18 — Created from the confirmed product decisions (content selection).
  Applies to both News and Blog modes by operating on the entry list rather than
  the source query.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
