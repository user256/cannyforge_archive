# Ticket 614: Enforce least-privilege Google OAuth and revoke credentials on disconnect

**Sprint:** 6 — Trust & Scale
**Status:** In progress
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

- [ ] The authorization scope contains Search Console read-only and adds
      Analytics read-only only when GA4 is explicitly enabled; the consent copy
      shows the scopes that will be requested before redirecting.
- [ ] OAuth callback state is validated and consumed before any success/error
      mutation; missing, expired, replayed, or foreign state cannot change token
      status.
- [ ] Access tokens are encrypted/authenticated at rest (or kept only in an
      appropriately bounded non-persistent cache) under the ticket 605 cipher
      design; migration from the current plaintext option is tested.
- [ ] Disconnect makes a best-effort call to Google's revocation endpoint before
      local deletion and reports whether remote revocation succeeded; cleanup is
      idempotent when tokens are absent/expired.
- [ ] Ticket 606 reuses the same revocation service for uninstall instead of
      duplicating network/token logic.
- [ ] Controller tests cover scope selection, callback-error CSRF, state replay,
      remote revocation success/failure, and local cleanup after a failed remote
      call.
- [ ] `readme.txt` and `docs/GOOGLE.md` describe requested scopes, token storage,
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

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed separately.
