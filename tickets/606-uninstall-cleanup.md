# Ticket 606: uninstall.php — clean removal of options, transients, and Google tokens

**Sprint:** 6 — Trust & Scale
**Status:** In review
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

## Inventory (the spec)

Found by grepping `update_option|add_option|set_transient|delete_option|delete_transient`
across `src/` and reading each literal key at its declaration site.

### Options (`get_option()` / `update_option()`, per-site — not network options)

| Option key | Source |
| --- | --- |
| `cannyforge_archive_settings` | `src/Core/Settings/OptionsSettingsRepository.php::OPTION_KEY` |
| `cannyforge_archive_google_settings` | `src/Integration/Google/GoogleSettingsStore.php::OPTION_KEY` |
| `cannyforge_archive_google_refresh_token` | `src/Integration/Google/GoogleTokenStore.php::REFRESH_TOKEN_KEY` |
| `cannyforge_archive_google_access_token` | `src/Integration/Google/GoogleTokenStore.php::ACCESS_TOKEN_KEY` |
| `cannyforge_archive_google_token_expires_at` | `src/Integration/Google/GoogleTokenStore.php::ACCESS_TOKEN_EXPIRES_AT_KEY` |
| `cannyforge_archive_google_connection_status` | `src/Integration/Google/GoogleTokenStore.php::STATUS_KEY` |
| `cannyforge_archive_google_ga4_cache` | `src/Integration/Google/Ga4CacheStore.php::OPTION_KEY` |
| `cannyforge_archive_google_search_console_cache` | `src/Integration/Google/SearchConsoleCacheStore.php::OPTION_KEY` |

### Fixed-name transients (`set_transient()` / `delete_transient()`)

One per `Mode` case (`src/Contracts/Settings/Mode.php`: `blog` / `news` / `hybrid`),
built from `ArchiveCache::PREFIX` in `src/Core/Cache/ArchiveCache.php`:

- `cannyforge_archive_html_blog`
- `cannyforge_archive_html_news`
- `cannyforge_archive_html_hybrid`

### Dynamically-suffixed transient (no fixed key)

- `cannyforge_archive_google_oauth_{state}` — `GoogleConnectionController::STATE_PREFIX`
  in `src/Admin/GoogleConnectionController.php`. `{state}` is a random 32-char
  value per connect attempt (`wp_generate_password()`), so there is no single
  key `delete_transient()` can address. `uninstall.php` removes every row
  matching this prefix directly via a prepared `$wpdb` `LIKE` query against
  the current site's options table (transients are stored as
  `_transient_{name}` / `_transient_timeout_{name}` options).

No other `update_option`/`add_option`/`set_transient` call sites exist in `src/`.

## Acceptance criteria

- [x] Every option name, transient prefix, and cache key the plugin writes is
      inventoried in this ticket (grep `update_option|set_transient` across
      `src/`) — the list is the spec. See the Inventory section above.
- [x] `uninstall.php` deletes all of them, guards on `WP_UNINSTALL_PLUGIN`,
      and handles multisite (iterate sites or use network-aware deletion).
- [x] Google tokens are revoked with Google (best-effort call to the token
      revocation endpoint) before local deletion, so a stale grant doesn't
      linger in the user's Google account.
- [x] Deactivate → reactivate preserves settings (regression check — the
      cleanup must live in uninstall, not deactivation).
- [x] The file ships in `dist/` (verify `.distignore` doesn't exclude it) and
      passes the WordPressAudit.md checks.
- [ ] Integration test (once ticket 603 lands): install → configure →
      uninstall → assert no `cannyforge` rows remain in `wp_options`. Not
      done — ticket 603 (the real-WordPress integration rig) hasn't merged
      yet, so there's no harness to run this assertion against yet. See
      decisions log.

## Out of scope

- A "keep my settings on uninstall" toggle — default to clean removal; file
  a follow-up if users ask.

## Dependencies

- **Blocks:** none
- **Blocked by:** none (integration assertion joins when 603 lands)
- **External:** none

## Notes / decisions log

- 2026-07-21 — Cleanup logic lives in a new `src/Bootstrap/UninstallCleaner.php`
  class (revoke + delete the 8 known options + 3 fixed-name transients),
  rather than as procedural code in `uninstall.php`, so it's unit-testable
  like every other collaborator in this codebase. `uninstall.php` itself is
  thin: the `WP_UNINSTALL_PLUGIN` guard, multisite iteration via
  `get_sites()`/`switch_to_blog()`/`restore_current_blog()` calling
  `UninstallCleaner::clean_current_site()` once per site, and the direct
  `$wpdb` `LIKE` cleanup for the dynamically-suffixed OAuth state transient
  (the one row-shape `UninstallCleaner` can't address with a fixed key).
- 2026-07-21 — Token revocation reuses the existing
  `GoogleRevocationService` (`src/Integration/Google/GoogleRevocationService.php`),
  already written during ticket 614 specifically anticipating this ticket
  ("shared by disconnect and uninstall" is in its docblock) — no new
  revocation code needed, just wiring.
- 2026-07-21 — Deactivate → reactivate regression test
  (`tests/Bootstrap/PluginLifecycleTest.php`) requires the real
  `cannyforge-archive.php` and invokes the actual closures registered via
  `register_activation_hook()`/`register_deactivation_hook()`, rather than
  re-describing their intent — a future change that adds cleanup to the
  deactivation hook would fail this test. Confirms today's deactivation hook
  only flushes rewrite rules and never touches stored data.
- 2026-07-21 — Tooling coverage: added `uninstall.php` to `phpcs.xml.dist`,
  `phpstan.neon.dist`, and `rector.php` (same treatment as
  `cannyforge-archive.php`), and added it to `tests/Packaging/DistributablePackageTest.php`'s
  allow-list plus a dedicated assertion that it ships in the built ZIP.
  `composer dist` was run manually and confirmed `uninstall.php` and
  `src/Bootstrap/UninstallCleaner.php` both land in
  `dist/cannyforge-archive-0.1.1.zip`.
- 2026-07-21 — Integration-test acceptance criterion left unchecked on
  purpose: ticket 603 (real-WordPress integration rig) is running in
  parallel and hasn't merged, so there's no harness yet to run
  "install → configure → uninstall → assert no `cannyforge` rows remain in
  `wp_options`" against a real WordPress install. Follow-on step once 603
  lands: add that assertion to whatever integration suite it introduces,
  exercising the full uninstall flow (including multisite) end-to-end.
- 2026-07-21 — Found and filed as a separate follow-up
  (`tickets/618-test-shim-redirect-conflict.md`), **not fixed here** as it's
  unrelated to this ticket: `tests/wp-hooks-shim.php` and
  `tests/wp-admin-post-shim.php` both define `wp_safe_redirect()` (each
  guarded with `function_exists()`); load order in `tests/bootstrap.php`
  means the non-throwing version always wins, so every
  `GoogleConnectionControllerTest` path that redirects falls through into a
  real `exit;` in production code, silently killing the whole PHPUnit
  process with exit code 0 (no failure reported). Reproduced on a clean
  `main` checkout via `git stash`, confirming it predates this ticket. This
  is why `composer test`/`composer qa` can't currently complete end-to-end;
  see the report for how this ticket's own changes were verified around it.
- 2026-07-21 — Also noted, not filed as its own ticket (minor, cosmetic,
  unrelated): `tests/Admin/SettingsViewTest.php::test_renders_preview_link`
  fails on a clean `main` checkout too — it expects link text "Open" where
  the settings view now renders "Preview Archive", likely copy drift from
  ticket 613's UI pass without updating this assertion.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
