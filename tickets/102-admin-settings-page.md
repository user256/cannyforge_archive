# Ticket 102: Admin settings page UI

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

The brief specifies an "HTML Sitemap Generator Settings" admin screen with a
Blog/News toggle, a pagination control, archive link-type checkboxes, user
filter checkboxes, and a mode-dependent right-hand panel (Blog URL list / News
recent-window). This is the operator's only control surface; it must read and
write the ticket-101 settings model.

## Goal

A WordPress admin page that renders the brief's mock-up and persists every
control through the settings model.

## Acceptance criteria

- [ ] A menu entry registers an admin page under a `cannyforge`-prefixed slug.
- [ ] Controls match the mock-up in [`docs/PLAN.md`](../docs/PLAN.md): mode
      toggle, pagination number input (default 1), Title/Description/Featured
      Image checkboxes, the five filter checkboxes, and the mode-dependent
      panel (Blog: max-URLs + URL list textarea/CSV; News: recent-window hours).
- [ ] The right-hand panel label switches between "Create Blog Sitemap" and
      "Create News Sitemap" with the mode toggle.
- [ ] Save uses a nonce + capability check (`manage_options`) and writes via
      the ticket-101 repository.
- [ ] `composer qa` passes; admin rendering/escaping satisfies WPCS.

## Out of scope

- The actual archive page generation (ticket 103) and pagination (107).
- Final-version Snowflake/Adobe controls.

## Dependencies

- **Blocks:** none
- **Blocked by:** 101
- **External:** none

## Approach (optional)

Keep markup in a small view/template and the wiring in a thin admin class under
a dedicated `src/Admin` namespace — note `phparkitect.php` forbids `*Settings`
classes from depending on `CannyForge\Archive\Admin`, so the settings model
must not reach into this layer.

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
