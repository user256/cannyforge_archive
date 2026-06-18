# Ticket 108: Branding & admin styling

**Sprint:** 1 — Settings & MVP
**Status:** Done
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

- [x] The settings page header uses a CannyForge logo from `assets/branding/`
      (`cannyforge-font-dark.svg` wordmark), rendered by `SettingsView` when a
      base URL is configured.
- [x] Admin and front-end styles follow the [`docs/DESIGN.md`](../docs/DESIGN.md)
      palette/typography tokens (violet `#6d4aff`, royal-purple headings,
      lavender card surfaces, gold top edge, pill controls, violet focus rings,
      Inter UI / Instrument Serif headings) without breaking core admin usability
      (`assets/css/admin.css`, `assets/css/archive.css`).
- [x] Stylesheets are enqueued, never inlined: admin style only on the plugin's
      page (`Admin\AdminAssets`, gated on the page hook suffix); front-end style
      only on the archive request (`Frontend\ArchiveAssets`, ticket 106).
- [x] `composer qa` passes (asset enqueueing satisfies WPCS).

## Out of scope

- Re-theming anything outside this plugin's two surfaces.
- Dark-mode toggle (use the provided assets as-is).

## Dependencies

- **Blocks:** none
- **Blocked by:** 102, 103
- **External:** brand assets in `assets/branding/`, design system in `docs/DESIGN.md`.

## Notes / decisions log

- 2026-06-18 — Implemented. Light mode only (per scope). Brand CSS uses the
  design-system tokens; the front-end stylesheet also styles the ticket-107
  pagination block (pill page links, gold-edged "View Archive"). Logo wired
  through a base-URL injected into `SettingsView` (defaults empty so unit tests
  construct it without a URL). Admin/front-end enqueue controllers both gate so
  nothing loads globally.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
