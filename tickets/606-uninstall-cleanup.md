# Ticket 606: uninstall.php — clean removal of options, transients, and Google tokens

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

The plugin has no `uninstall.php`. Deleting it leaves behind the settings
option, fragment-cache transients (ticket 206), OAuth state transients,
Ga4/Search Console cache stores, and — worst — the encrypted Google tokens.
Leaving third-party credentials in the database after the user has removed
the plugin is both a trust problem and a wp.org review flag, and Sprint 5's
audit remediation (501) did not cover it.

## Goal

Uninstalling the plugin removes every row it created, including credentials,
while deactivation alone leaves data intact.

## Acceptance criteria

- [ ] Every option name, transient prefix, and cache key the plugin writes is
      inventoried in this ticket (grep `update_option|set_transient` across
      `src/`) — the list is the spec.
- [ ] `uninstall.php` deletes all of them, guards on `WP_UNINSTALL_PLUGIN`,
      and handles multisite (iterate sites or use network-aware deletion).
- [ ] Google tokens are revoked with Google (best-effort call to the token
      revocation endpoint) before local deletion, so a stale grant doesn't
      linger in the user's Google account.
- [ ] Deactivate → reactivate preserves settings (regression check — the
      cleanup must live in uninstall, not deactivation).
- [ ] The file ships in `dist/` (verify `.distignore` doesn't exclude it) and
      passes the WordPressAudit.md checks.
- [ ] Integration test (once ticket 603 lands): install → configure →
      uninstall → assert no `cannyforge` rows remain in `wp_options`.

## Out of scope

- A "keep my settings on uninstall" toggle — default to clean removal; file
  a follow-up if users ask.

## Dependencies

- **Blocks:** none
- **Blocked by:** none (integration assertion joins when 603 lands)
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
