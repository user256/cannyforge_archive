# Ticket 619: `SettingsViewTest::test_renders_preview_link` fails standalone

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Discovered while verifying `composer qa` for ticket 601, after tracing why
`tests/Admin/SettingsViewTest.php` never seemed to run (see ticket 618: the
whole suite silently truncates before reaching it alphabetically). Run in
isolation (`vendor/bin/phpunit tests/Admin/SettingsViewTest.php`), 15 of 16
tests pass but `test_renders_preview_link` fails:

```
Failed asserting that '...<a class="cf-btn cf-btn-outline" href="http://example.test/archive/" target="_blank" rel="noopener noreferrer">Preview Archive <span class="dashicons dashicons-external" aria-hidden="true"></span></a>...'
contains "href=\"http://example.test/archive/\" target=\"_blank\" rel=\"noopener noreferrer\">Open".
```

The rendered markup has the right `href`/`target`/`rel` attributes but the
link text is "Preview Archive", not "Open" — the test's expected substring and
the view's actual copy have drifted, most likely during ticket 613 ("make
admin settings UI truthful, complete, and accessible"), which touched this
exact area. Confirmed present on `main` via `git stash` — not introduced by
ticket 601.

## Goal

`SettingsViewTest::test_renders_preview_link` passes, and its assertion
reflects the link text the view actually renders (or the view is changed back
to match the test's original intent, whichever is correct product behaviour).

## Acceptance criteria

- [ ] Determine which side is right: should the preview link read "Open" (and
      `SettingsView`/`SettingsSectionsView` needs a copy fix) or "Preview
      Archive" (and the test's expected substring needs updating)?
- [ ] `vendor/bin/phpunit tests/Admin/SettingsViewTest.php` passes in full,
      standalone.
- [ ] `composer qa` is genuinely green (verify after ticket 618 lands, so a
      full-suite run actually reaches and executes this file).

## Out of scope

- Ticket 618's shim-collision bug that hid this failure — filed separately.

## Dependencies

- **Blocks:** none
- **Blocked by:** 618 (to get a trustworthy full-suite run confirming no other
  hidden failures exist alongside this one)
- **External:** none

## Notes / decisions log

- 2026-07-21 — Found during ticket 601's `composer qa` verification. Confirmed
  pre-existing on `main` via `git stash`; not caused by ticket 601's changes.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
