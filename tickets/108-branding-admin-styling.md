# Ticket 108: Branding & admin styling

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

The plugin ships with the CannyForge brand assets
([`assets/branding/`](../assets/branding/)) and a design system
([`docs/DESIGN.md`](../docs/DESIGN.md)). The admin settings page (ticket 102)
and the front-end archive (ticket 103) should look like CannyForge rather than
default WordPress chrome.

## Goal

Apply the CannyForge design system (logo, palette, typography) to the admin
settings page and the front-end archive, within WordPress norms.

## Acceptance criteria

- [ ] The settings page header uses a CannyForge logo from
      `assets/branding/` (icon + wordmark).
- [ ] Admin and front-end styles follow the [`docs/DESIGN.md`](../docs/DESIGN.md)
      palette/typography tokens (violet `#6d4aff`, deep purple text, lavender
      surfaces, pill controls) without breaking core admin usability.
- [ ] Stylesheets are enqueued (admin style only on our page; front-end style
      only on the archive page) — never inlined globally.
- [ ] `composer qa` passes (asset enqueueing satisfies WPCS).

## Out of scope

- Re-theming anything outside this plugin's two surfaces.
- Dark-mode toggle (use the provided assets as-is).

## Dependencies

- **Blocks:** none
- **Blocked by:** 102, 103
- **External:** brand assets in `assets/branding/`, design system in `docs/DESIGN.md`.

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
