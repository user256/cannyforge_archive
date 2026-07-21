# Ticket 609: Accessibility pass on the archive page and filter UI (WCAG 2.2 AA)

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** background-agent
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

- [x] The results region is an `aria-live="polite"` region (or focus is
      managed onto a results heading); result-count changes are announced
      ("N results for 'query'").
- [x] All filter controls have programmatic labels; the search input has a
      visible label or equivalent; pagination controls are reachable and
      operable by keyboard with a visible focus indicator.
- [x] A "no results" state exists and is announced (currently verify —
      and fix if the list just goes empty silently).
- [x] Default theme palette in `assets/css/archive.css` passes 4.5:1 contrast;
      the admin theming panel shows a contrast warning when a chosen
      foreground/background pair fails it.
- [x] `prefers-reduced-motion` respected for any transitions.
- [ ] Automated check wired into CI where 603's rig allows (axe-core against
      the rendered archive page); remaining manual checks (screen-reader
      walkthrough) listed in the README smoke section. **Blocked on 603**
      (real-WordPress integration rig), which is not yet merged — see
      decisions log. The screen-reader walkthrough manual checks are added
      to the README smoke checklist regardless.

## Out of scope

- Admin settings-page accessibility — WordPress core admin patterns carry
  most of it; file a follow-up if 602's view tests surface problems.

## Dependencies

- **Blocks:** none
- **Blocked by:** none (axe automation criterion activates when 603 merges)
- **External:** none

## Notes / decisions log

- 2026-07-21 — Verified `archive-filters.js` and the server-rendered markup
  before changing anything. `ArchiveRenderer::render()` already emits the
  results-summary status div as `aria-live="polite"` (`data-results-summary`),
  and `FilterControlsRenderer` already wraps every filter control (search box
  + selects) in a `<label>` with visible text plus an `aria-label` on search —
  so the live-region and label acceptance criteria were largely already true
  going in. The ticket's "current a11y footprint is a single `aria-current`"
  framing describes `archive-filters.js` specifically (accurate — the JS
  itself only set `aria-current`), not the PHP-rendered markup around it.
- 2026-07-21 — The "no results" state existed (`data-empty-state` paragraph,
  toggled via `data.total !== 0`) but its wording didn't include the search
  query and wasn't verified to compose well with the live region. Reworked
  `showResults()`/added `resultSummaryText()` in `archive-filters.js` so the
  aria-live summary now reads e.g. `3 results for "budget"` or
  `No results for "budget". Try a different search or clear your filters.`,
  covering both the announcement and the "N results for 'query'" wording the
  acceptance criterion asks for. Added `tests/js/archive-filters.test.js`.
- 2026-07-21 — Found a real bug while auditing pagination keyboard/focus
  behaviour: the AJAX-rendered pagination buttons in `archive-filters.js` used
  class `cannyforge-archive__page`, but `archive.css` (and the server-rendered
  `PaginationRenderer`) only styles `.cannyforge-pagination__page` — so the
  client-side pagination controls rendered completely unstyled (no focus
  ring, no current-page highlight). Fixed the class name to match, added a
  `.cannyforge-pagination__page-status` style for the "Page X of Y" text, and
  added explicit `:focus-visible` outlines for pagination controls, the
  archive link, and the filters reset button.
- 2026-07-21 — Computed WCAG contrast ratios for every default theme colour
  pair (`Theme::DEFAULT_*_COLOR` / `assets/css/archive.css`) rather than
  assuming: text `#1b143f` on surface `#ffffff` is ~17.2:1, accent `#6d4aff`
  on `#ffffff` is ~5.16:1 — both already pass 4.5:1. The one real failure was
  the search placeholder, rendered at `color-mix(... var(--cf-text) 55%,
  transparent)` over a near-white input background: ~3.92:1, below AA.
  Raised the mix to 65% (~5.44:1) to fix it. The border colour
  (`#d8dbe8` on `#ffffff`, ~1.38:1) also fails, but that's WCAG 1.4.11
  (non-text/UI-component contrast), a different success criterion than this
  ticket's literal "4.5:1" criterion, and fixing it means re-tuning a shared
  design token with real visual side effects — filed as follow-up ticket 618
  rather than silently reworking the theme's look under this ticket's
  changes.
- 2026-07-21 — No existing contrast-ratio math in the codebase (checked
  before adding anything). Implemented `hexToRgb`/`relativeLuminance`/
  `contrastRatio` as small pure functions in `assets/js/admin.js` (no new
  dependency) and wired `initContrastWarning()` into the "Edit Colours"
  dialog (`SettingsSectionsView::render_colour_dialog`): a `role="status"`
  paragraph shows a plain-language warning the moment the text/surface or
  accent/surface pair drops below 4.5:1, live as the user edits the colour
  pickers, and clears once fixed. Covered by `tests/js/contrast.test.js`.
- 2026-07-21 — Added a `prefers-reduced-motion: reduce` block to
  `archive.css` that zeroes every animation/transition duration and cancels
  the hover/focus transform shifts (translateY/translateX/scale), scoped to
  that file per the ticket's explicit wording (admin.css's few transitions
  are out of this ticket's front-end scope).
- 2026-07-21 — Automated axe-core-in-CI criterion is genuinely blocked on
  ticket 603 (real-WordPress integration rig), which is running in parallel
  and not yet merged — did not attempt to wire CI for it. Added the
  screen-reader walkthrough as an explicit manual smoke-check item in
  `README.md` instead, matching the pattern already used there for other
  603-blocked manual checks.
- 2026-07-21 — While verifying `composer qa`, found two pre-existing,
  unrelated failures (confirmed via `git stash` against clean `main`, not
  caused by this ticket's diff): (1) `vendor/bin/phpunit` silently stops
  partway through `Admin/GoogleConnectionControllerTest` with exit code 0 and
  no summary, meaning most of the suite (everything alphabetically after it)
  never actually runs under `composer qa`; (2)
  `SettingsViewTest::test_renders_preview_link` fails against current
  `SettingsView` markup regardless. Filed as ticket 619 rather than fixed
  here (unrelated domain — Google OAuth tests / a settings-page test, not
  archive accessibility). Verified this ticket's own PHP changes by running
  `vendor/bin/phpunit --filter '^((?!GoogleConnectionControllerTest).)*$'`,
  which reaches the full remaining 265 tests and shows only that one
  pre-existing, unrelated failure.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
