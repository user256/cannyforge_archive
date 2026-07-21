# Ticket 614: Enforce least-privilege Google OAuth and revoke credentials on disconnect

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** background-agent
**Estimate:** M
**Priority:** P0 — security/privacy

---

## Context

The admin UI describes GA4 as optional, but every OAuth connection requests both
Search Console and Analytics read scopes. The access token is persisted in
plaintext while only the refresh token/client secret use `SecretCipher`.
Disconnect clears local options but does not revoke Google's grant. Finally, an
OAuth callback carrying `error` mutates connection status before validating the
state transient, leaving a CSRF-able error path. These gaps are adjacent to, but
not fully covered by, tickets 602, 605, and 606.

## Goal

Google access is limited to the features the administrator enabled, all stored
credentials receive consistent protection, and disconnect invalidates the
remote grant as well as local state.

## Acceptance criteria

- [x] The authorization scope contains Search Console read-only and adds
      Analytics read-only only when GA4 is explicitly enabled; the consent copy
      shows the scopes that will be requested before redirecting.
- [x] OAuth callback state is validated and consumed before any success/error
      mutation; missing, expired, replayed, or foreign state cannot change token
      status.
- [x] Access tokens are encrypted/authenticated at rest (or kept only in an
      appropriately bounded non-persistent cache) under the ticket 605 cipher
      design; migration from the current plaintext option is tested.
- [x] Disconnect makes a best-effort call to Google's revocation endpoint before
      local deletion and reports whether remote revocation succeeded; cleanup is
      idempotent when tokens are absent/expired.
- [ ] Ticket 606 reuses the same revocation service for uninstall instead of
      duplicating network/token logic.
- [x] Controller tests cover scope selection, callback-error CSRF, state replay,
      remote revocation success/failure, and local cleanup after a failed remote
      call.
- [x] `readme.txt` and `docs/GOOGLE.md` describe requested scopes, token storage,
      disconnect/revocation behavior, and the distinction between Search Console
      only and Search Console + GA4.
- [ ] `composer qa` passes.

## Out of scope

- Cipher algorithm/key-rotation implementation details owned by ticket 605.
- Automatic background cache refresh.

## Dependencies

- **Blocks:** 606, 699
- **Blocked by:** 602 for controller harness; coordinate stored-token changes
  with 605
- **External:** Google OAuth/revocation endpoints and live credentials for 699

## Notes / decisions log

- 2026-07-21 — `GoogleConnectionController::start_connect()` always requests
  `analytics.readonly`; `GoogleTokenStore::save_access_token()` stores the access
  token directly in an option.
- 2026-07-21 — Implemented without ticket 602 (no dedicated controller test
  harness exists yet) and without ticket 605 (SecretCipher hardening/rotation
  not in this batch). Details:
  - **602 gap**: added a small, scoped admin-post shim
    (`tests/wp-admin-post-shim.php`, `tests/WpDieException.php`,
    `tests/WpRedirectException.php`) covering only the WordPress primitives
    `GoogleConnectionController` touches (capability check, nonce check,
    `wp_die`, `wp_redirect`/`wp_safe_redirect`, `add_query_arg`,
    `wp_generate_password`, `get_current_user_id`, `sanitize_text_field`,
    `wp_unslash`). `wp_die`/`wp_redirect`/`wp_safe_redirect` throw catchable
    exceptions instead of terminating the process, so tests can assert on the
    outcome. This is not a general controller harness — a real one (602)
    should likely supersede/absorb it.
  - **605 gap**: reused the existing `SecretCipher` as-is for the access
    token (same pattern already used for the refresh token and client
    secret); did not touch its algorithm or add key rotation. 605 should be
    aware that `GoogleTokenStore::save_access_token()`/`valid_access_token()`
    now also go through `SecretCipher::encrypt()`/`decrypt()`, so any cipher
    format change needs to stay compatible with (or provide a migration for)
    the access token, not just the refresh token/client secret.
  - **Live Google testing**: the revocation call
    (`GoogleRevocationService` → `https://oauth2.googleapis.com/revoke`) is
    unit-tested with mocked HTTP only; no live Google credentials were
    available in this environment. Live verification is deferred to 699,
    consistent with prior Google-integration tickets in this repo.
- 2026-07-21 — Extracted least-privilege scope selection into
  `Integration\Google\GoogleOauthScopePolicy` (rather than static methods on
  `GoogleConnectionController`) and remote revocation into
  `Integration\Google\GoogleRevocationService`, both away from the Admin
  layer. This keeps `GoogleConnectionController` from crossing PHPMD's
  400-line class-length budget and gives ticket 606 (uninstall cleanup, not
  in this batch) a ready-to-reuse, WordPress-runtime-optional revocation
  service — `GoogleRevocationService::revoke_and_clear()` — so uninstall can
  call the same code path instead of re-implementing the revoke HTTP call.
  606 itself (the `uninstall.php` file) was not implemented here; it is
  still "Not started" and out of scope for this ticket per the assignment.
- 2026-07-21 — `composer qa` does not pass repo-wide. Confirmed (by stashing
  this ticket's changes and re-running) that `composer cs`/`composer
  stan`/`composer test`/`composer rector` all fail on the same pre-existing,
  unrelated baseline issues before any of this ticket's changes are applied
  (`src/Admin/SettingsView.php` phpcs/phpstan errors, a `PaginationRenderer.php`
  phpstan comparison warning, `src/Admin/AdminAssets.php` rector suggestion, and
  the `SettingsViewTest::test_renders_preview_link` failure from the
  in-progress settings-UI rebuild). That debt is tracked separately under
  ticket 611. Scoped to the files this ticket touches, `composer
  cs`/`stan`/`arch`/`deptrac`/`mess`/`test` are all clean, and `mess` in
  particular confirms no *new* class/method-length violations were introduced
  (`ModeSettingsPanelView`'s existing over-budget class length grew by the ~26
  lines this ticket added, but was already over budget before this change).

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
