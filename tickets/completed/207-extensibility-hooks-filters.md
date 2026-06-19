# Ticket 207: Extensibility hooks and filters for third-party integrations

**Sprint:** 2 — Hardening & fit
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The current architecture is highly decoupled but very closed. Other than `SeoHead.php`, there are no `apply_filters` or `do_action` hooks exposed. This makes it impossible for theme developers or other plugins to modify the archive HTML, filter the list of URLs dynamically, or inject custom CSS without modifying core plugin code.

## Goal

Introduce a suite of WordPress action and filter hooks at key integration points so third-party developers can safely extend the plugin.

## Acceptance criteria

- [ ] `apply_filters('cannyforge_archive_entries', $entries)` is exposed before rendering the HTML to allow dynamic addition/removal of entries.
- [ ] `apply_filters('cannyforge_archive_theme_css', $css, $theme)` is exposed in `ThemeCssBuilder` to allow themes to override or extend the injected CSS variables.
- [ ] `do_action('cannyforge_archive_before_render')` and `do_action('cannyforge_archive_after_render')` are added to the page template rendering flow.
- [ ] The new hooks are documented in `docs/` or the README to guide developers on how to use them.

## Out of scope

- Building integrations for specific third-party plugins (e.g., Yoast, ACF). We are only providing the foundational hooks.
- Refactoring internal business logic to use hooks for its own operations; these hooks are strictly for external consumers.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Approach (optional)

Add the hooks in `src/Frontend/ArchivePage.php`, `src/Core/Archive/ThemeCssBuilder.php`, and potentially `src/Core/Archive/ArchiveRenderer.php`. Ensure that test coverage verifies that filters can modify the payload and actions are correctly invoked.

## Notes / decisions log

- 

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
