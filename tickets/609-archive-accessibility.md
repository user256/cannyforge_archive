# Ticket 609: Accessibility pass on the archive page and filter UI (WCAG 2.2 AA)

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The archive page is a public, front-facing surface on every site that
installs the plugin, and its interactivity (live search, filter selects,
client-side pagination) is exactly the kind of dynamic UI that fails screen
readers by default. Current a11y footprint in `assets/js/archive-filters.js`
is a single `aria-current` attribute. Results updating without a page reload
are invisible to assistive tech unless announced; keyboard behaviour and
focus management are untested; the theming controls (ticket 204) let site
owners pick colour pairs with no contrast guidance.

## Goal

The archive page and its search/filter/pagination UI meet WCAG 2.2 AA, with
the checks automated where tooling allows.

## Acceptance criteria

- [ ] The results region is an `aria-live="polite"` region (or focus is
      managed onto a results heading); result-count changes are announced
      ("N results for 'query'").
- [ ] All filter controls have programmatic labels; the search input has a
      visible label or equivalent; pagination controls are reachable and
      operable by keyboard with a visible focus indicator.
- [ ] A "no results" state exists and is announced (currently verify —
      and fix if the list just goes empty silently).
- [ ] Default theme palette in `assets/css/archive.css` passes 4.5:1 contrast;
      the admin theming panel shows a contrast warning when a chosen
      foreground/background pair fails it.
- [ ] `prefers-reduced-motion` respected for any transitions.
- [ ] Automated check wired into CI where 603's rig allows (axe-core against
      the rendered archive page); remaining manual checks (screen-reader
      walkthrough) listed in the README smoke section.

## Out of scope

- Admin settings-page accessibility — WordPress core admin patterns carry
  most of it; file a follow-up if 602's view tests surface problems.

## Dependencies

- **Blocks:** none
- **Blocked by:** none (axe automation criterion activates when 603 merges)
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
