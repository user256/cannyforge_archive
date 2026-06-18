# Ticket 106: Client-side search & filters

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** L

---

## Context

The brief specifies user-defined Search/Filters (binary on/off), explicitly
done **client-side using JS** over the already-rendered archive: search box,
category, tag, month+year, and author filters. They enhance the server-rendered
archive (ticket 103) without changing its crawlable output.

## Goal

Progressive-enhancement JS that filters the rendered archive entries by the
enabled controls, with zero impact on the no-JS crawlable output.

## Acceptance criteria

- [x] Only the filters enabled in settings render and initialise
      (`Core\Archive\FilterControlsRenderer` emits controls per `Filters`; the
      JS only binds the controls present).
- [x] Search box does live client-side text matching over visible entries
      (`assets/js/archive-filters.js`).
- [x] Category, tag, month+year, and author filters narrow the visible set;
      combining filters intersects (AND) — search ∧ each enabled select.
- [x] Entries carry the needed filter metadata as data-attributes emitted by
      the ticket-103 generator (so JS needs no extra request); select options
      are derived from those same entries server-side.
- [x] Works as progressive enhancement: controls live in a `<form>` above the
      crawlable server-rendered list; with JS disabled the full archive is still
      present (the JS only toggles `hidden`).
- [x] Script + style are enqueued (not inlined) and only on the archive request
      (`Frontend\ArchiveAssets` gates on the archive query var; script in footer).

## Out of scope

- Server-side search (the brief is explicit: client-side).
- Pagination of the filtered set.

## Dependencies

- **Blocks:** none
- **Blocked by:** 103 (needs the rendered entries + data-attributes), 101 (toggles)
- **External:** none

## Notes / decisions log

- 2026-06-18 — Implemented. Confirmed decision: PHP-side tested, ship vanilla
  JS/CSS (no JS test runtime added to this PHP-only repo). The PHP that the
  suite covers is `FilterControlsRenderer` (enabled-filter gating, option
  derivation, escaping) and `ArchiveAssets` (archive-only enqueue). The filter
  behaviour itself lives in `assets/js/archive-filters.js`, verified by hand;
  worth a Sprint-2 ticket if a JS unit harness is ever wanted. Starter
  `assets/css/archive.css` added here; ticket 108 layers the brand tokens on.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
