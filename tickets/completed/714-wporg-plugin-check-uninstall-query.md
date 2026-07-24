# Ticket 714: Make the dynamic-transient uninstall query pass WordPress Plugin Check

**Sprint:** 6 — Trust & Scale (wp.org submission blocker)
**Status:** Completed
**Owner:** unassigned
**Estimate:** S
**Priority:** P0 — WordPress.org submission gate

---

## Context

The distributable plugin passes its normal quality suite, but the official
WordPress Plugin Check 2.0.0 reports a security warning for
`uninstall.php:88`: `PluginCheck.Security.DirectDB.UnescapedDBParameter`.
The dynamic-transient cleanup prepares the `LIKE` values, then inserts
`$wpdb->options` into a `{options_table}` placeholder with `str_replace()`
immediately before `$wpdb->query()`.

The table name is trusted WordPress state, but the submitted artifact still
looks like it passes an unescaped value into a destructive query. Plugin Review
must be able to verify the query mechanically; an ignored warning leaves a
reviewer-facing security finding in the ZIP and can block approval.

## Goal

The uninstall cleanup removes every dynamic Google transient on single-site
and multisite installs without any WordPress Plugin Check security warning.

## Acceptance criteria

- [x] Replace the post-`prepare()` `str_replace()` query assembly with the
      WordPress-supported prepared identifier pattern (the plugin requires
      WordPress 6.4+) or another Plugin Check-recognised approach; do not
      concatenate a table name into SQL.
- [x] Preserve deletion of both value and timeout rows for every supported
      prefix: OAuth-state, Search Console property-list, and GA4 property-list
      transients.
- [x] Add/adjust unit coverage for the exact SQL
      shape, including the multisite current-site table prefix.
- [x] `wp plugin check cannyforge-archive --format=json --mode=new` reports
      no errors or warnings for `uninstall.php`.
- [x] `composer qa`, `composer test:integration`, and `composer dist` pass;
      scan the built ZIP, not only the repository checkout.

## Out of scope

- Sweeping bounded search-result/rate-limit transients that the readme says may
  expire naturally.
- Altering the user-facing uninstall data-retention policy.

## Dependencies

- **Blocks:** 699 (Sprint 6 release/submission gate)
- **Blocked by:** none
- **Related:** 606 (uninstall cleanup), 708 (user-scoped property transients)

## Notes / decisions log

- 2026-07-23 — Filed from an isolated WordPress 6.4+/PHP 8.1 Plugin Check
  run against the `composer dist` package. The full check completed with one
  warning and no errors:
  `PluginCheck.Security.DirectDB.UnescapedDBParameter` at `uninstall.php:88`.
  The warning-free run excluding warnings completed with no errors, confirming
  this is the remaining formal submission finding.
- 2026-07-23 — Replaced the post-prepare table-name substitution with the
  `%i` prepared identifier placeholder (supported above this plugin's WordPress
  6.4 minimum). Extended the `$wpdb` test stand-in and uninstall test to prove
  the identifier is prepared and all three dynamic transient families are
  removed. A rebuilt package's full Plugin Check run reports no errors or
  warnings.
- 2026-07-23 — Verified `composer qa` (395 tests, 1,167 assertions),
  `npm test -- --runInBand --no-cache` (8 suites / 52 tests), rebuilt the ZIP,
  completed the disposable real-WordPress integration suite, and reran Plugin
  Check against the rebuilt mounted package with no findings.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is changed to `- [x]`.
4. A fresh package audit records zero Plugin Check findings.
