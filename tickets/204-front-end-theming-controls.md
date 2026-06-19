# Ticket 204: Front-end theming controls

**Sprint:** 2 — Hardening & Fit
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The archive is functional but visually plain, and it currently ships with no
site-specific styling controls. On a real install that makes the output feel
detached from the host theme, especially when dropped into differently branded
sites. The plugin needs basic front-end theming and a small set of admin-facing
controls so site owners can make it fit their theme without editing code.

## Goal

The archive has a better default front-end presentation and exposes basic style
controls so it can be tuned to match a site's look.

## Acceptance criteria

- [x] The settings page includes a front-end theming section with a small, concrete set of controls suitable for non-technical users.
- [x] The archive output applies the configured theming without breaking no-JS rendering or client-side filters.
- [x] The default styling is materially better than the current unthemed list on both desktop and mobile.
- [x] Relevant unit tests are updated and `vendor/bin/phpunit --testdox` passes.

## Out of scope

- A full visual builder.
- Theme-specific adapters or per-taxonomy style variants.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-06-18 — Filed from the request for “basic theming” plus user control so the archive fits a site's view.
- 2026-06-18 — Implemented with layout + colour controls in settings, inline CSS variables, upgraded archive styling, and asset loading on both the archive page and targeted archive listings.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
