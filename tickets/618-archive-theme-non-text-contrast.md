# Ticket 618: Non-text (UI component) contrast for the front-end theme's border colour

**Sprint:** unassigned
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Filed during ticket 609 (archive accessibility pass). Ticket 609's acceptance
criterion was scoped to WCAG 1.4.3 (4.5:1 text contrast), which the default
theme palette (`assets/css/archive.css`, `Theme::DEFAULT_*_COLOR`) now passes,
including a fix to the search placeholder's opacity. While verifying the
palette I also computed the default border colour's contrast, which is a
separate WCAG 2.2 success criterion (1.4.11 Non-text Contrast, AA) covering
UI component boundaries such as input/select borders — and it fails badly.

Computed contrast of `--cf-border: #d8dbe8` against `--cf-surface: #ffffff`
(both defaults) is **~1.38:1**, far under the 3:1 SC 1.4.11 requires for a
form control's visible boundary. The search box, filter selects, reset
button, and pagination controls all use this border colour at rest, and nothing
else (background delta, box-shadow) reliably marks their extent until
hover/focus. This is a real, separate accessibility gap that 609 did not fix,
since fixing it changes the visual theme (border hue/weight) beyond what that
ticket's literal 4.5:1 text-contrast criterion asked for, and re-tuning a
shared design token risks unrequested visual side effects better reviewed on
their own.

## Goal

The default front-end theme's border colour (and any other purely
decorative-but-load-bearing UI boundary colour) passes WCAG 2.2 SC 1.4.11
(>= 3:1 against its adjacent background), and the admin colour-picker
contrast warning (ticket 609) is extended to cover this pair too if a site
owner changes it.

## Acceptance criteria

- [ ] Default `border_color` (or the effective on-screen border) passes
      >= 3:1 against `surface_color` for every layout (default/cards/list).
- [ ] Decide and document whether border contrast alone is sufficient, or
      whether a resting-state background/shadow differentiation is added
      instead (either satisfies SC 1.4.11).
- [ ] `composer qa` green; `npm test` green if `admin.js`/`archive-filters.js`
      are touched.

## Out of scope

- Re-litigating the accent/text/surface text-contrast palette (609 already
  covers and passes this).

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **Related:** 609 (front-end/admin accessibility), 204 (theming controls)

## Notes / decisions log

- 2026-07-21 — Filed from ticket 609: computed contrast ratios for every
  CSS custom property pair in the default palette while verifying 609's
  4.5:1 acceptance criterion; the border/surface pair is the one failure,
  and it's a different success criterion (1.4.11, not 1.4.3) than 609 scoped.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
