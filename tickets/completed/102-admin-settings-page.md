# Ticket 102: Admin settings page UI

**Sprint:** 1 — Settings & MVP
**Status:** Done
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

- [x] A menu entry registers an admin page under the `cannyforge-archive` slug
      (`SettingsPage::add_menu_page` on the `admin_menu` hook).
- [x] Controls match the mock-up in [`docs/PLAN.md`](../docs/PLAN.md): mode
      toggle, pagination number input (default 1), Title/Description/Featured
      Image checkboxes, the five filter checkboxes, and the mode-dependent
      panel (Blog: max-URLs + URL list textarea; News: recent-window hours).
- [x] The right-hand panel switches between "Blog URLs to include" and
      "News Sitemap Settings" with the mode toggle (`SettingsView`).
- [x] Save uses a nonce + `manage_options` capability check and writes via the
      ticket-101 `SettingsRepositoryInterface` (`SettingsPage::maybe_save`).
- [x] `composer qa` passes; admin rendering/escaping satisfies WPCS (output
      escaped, `absint` on numeric fields, nonce verified before reading $_POST).

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

- 2026-06-18 — New `Admin` layer added to `deptrac.yaml` (Admin → Contracts +
  Core; Bootstrap → +Admin). Split into three classes to stay under the PHPMD
  budget: `SettingsPage` (menu/nonce/capability controller), `SettingsView`
  (escaped form rendering, owns the nonce action/field constants), and
  `SettingsFormParser` (pure $_POST → `Settings` mapper).
- 2026-06-18 — Replaced the template's example PHParkitect rule
  (`*Settings` name glob → not depend on Admin), which mis-fired on the admin
  classes (`Settings*`). Re-scoped it to the real settings value-object
  namespace `Contracts\Settings` (must not depend on Admin or Core), which is
  the boundary that actually matters.
- 2026-06-18 — CSV *import* (file upload) deferred; the textarea accepts
  comma/newline-separated URLs, which covers the brief's MVP "text input or CSV"
  for paste-in. A dedicated file-upload importer can be a follow-up if needed.
- 2026-06-18 — Tests run without WordPress via shims: hooks/menu
  (`wp-hooks-shim.php` + `HookSpy`) and escaping/i18n/form helpers
  (`wp-view-shim.php`). Admin classes covered by parser + view-render tests; the
  composition root asserts the `admin_menu` hook is registered.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
