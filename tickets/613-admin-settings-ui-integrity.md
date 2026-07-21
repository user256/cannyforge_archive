# Ticket 613: Make the redesigned admin settings UI truthful, complete, and accessible

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** M
**Priority:** P1 — pre-release UX

---

## Context

The current settings redesign renders several controls and statuses that do not
match behavior. “Reset to defaults”, the overflow button, preview device
controls, and icons have no implementation; “All changes saved” and “Draft
changes” are shown simultaneously and never track form state; the iframe shows
persisted output while claiming to reflect current settings. Mode radios are
`display:none`, leaving keyboard users without a focusable radio control, and
several actions use inline `onclick` handlers despite the new enqueued admin
script. Ticket 609 explicitly excludes admin accessibility.

## Goal

Every visible admin control performs its advertised action, state labels are
accurate, and the settings workflow is keyboard- and screen-reader-usable.

## Acceptance criteria

- [ ] Remove placeholder/dead controls or implement them with explicit tests:
      reset-to-defaults, overflow menu, device selector/icons, and preview
      controls.
- [ ] Dirty/saved state is derived from actual form changes and save results;
      the UI never shows “Draft changes” and “All changes saved” at the same
      time.
- [ ] Preview copy accurately distinguishes saved front-end output from unsaved
      form values; if live unsaved preview is implemented, its supported fields
      and refresh behavior are tested.
- [ ] Mode radios remain native, focusable form controls (visually hidden with
      an accessible technique, not `display:none`); selection visuals update on
      keyboard and pointer input without a page reload.
- [ ] Sidebar, accordion, modal, copy, colour-dialog, and save controls have
      visible focus states, usable accessible names, Escape/focus-return modal
      behavior, and no inline event-handler attributes.
- [ ] Responsive rules are exercised at desktop/tablet/mobile widths; the
      preview toggle does not target a permanently `display:none` panel.
- [ ] The Google JSON importer accepts only a Web OAuth client payload, rejects
      malformed/oversized/wrong-client uploads with an actionable notice, and
      never reports a silent successful import.
- [ ] Add focused JS tests (or Playwright coverage via ticket 603) for navigation,
      dirty state, dialogs, mode selection, and preview behavior.
- [ ] `composer qa` passes and ticket 602's render/round-trip coverage remains
      green.

## Out of scope

- Public archive accessibility (ticket 609).
- A second visual redesign.

## Dependencies

- **Blocks:** 699
- **Blocked by:** 611 for a green baseline; browser automation may reuse 603
- **External:** none

## Notes / decisions log

- 2026-07-21 — Audit compared `SettingsView.php`, `admin.js`, and `admin.css`;
  the listed controls have no matching listener or form action.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
