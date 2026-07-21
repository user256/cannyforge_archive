# Ticket 605: Harden SecretCipher — authenticated encryption, fail-loud fallbacks, key rotation

**Sprint:** 6 — Trust & Scale
**Status:** In review
**Owner:** unassigned
**Estimate:** M

---

## Context

`Integration/Google/SecretCipher` (ticket 404) has four weaknesses:

1. **Unauthenticated encryption.** AES-256-CBC with no MAC — ciphertext is
   malleable and decryption can't detect tampering or key mismatch reliably.
2. **Silent plaintext fallback.** If OpenSSL is absent, `encrypt()` returns
   the plaintext and it is stored unencrypted with no admin-visible warning.
3. **Permanent plaintext passthrough.** Untagged values decrypt to
   themselves forever; legacy plaintext secrets are never migrated to
   encrypted form.
4. **Salt-rotation data loss.** The key derives from `wp_salt('auth')`; a
   site that rotates salts (a recommended incident response!) has every
   stored token decrypt to `''` with no explanation, and the Google
   connection just silently stops working.

## Goal

Stored Google secrets use authenticated encryption, degrade loudly instead of
silently, and a broken key produces an actionable admin notice rather than a
mystery disconnect.

## Acceptance criteria

- [x] Encryption uses an AEAD: `sodium_crypto_secretbox` (ext-sodium ships
      with PHP ≥ 7.2 and is already in the CI extension list) or AES-256-GCM;
      new values are written under a new tag (e.g. `enc2:`).
- [x] Decrypt reads both `enc2:` and legacy `enc:`; on the first successful
      legacy read, the value is re-encrypted and re-stored under `enc2:`
      (opportunistic migration). Same for legacy plaintext values.
- [x] If no AEAD backend is available, `encrypt()` refuses to store the
      secret and the admin sees a persistent notice explaining why, instead
      of plaintext landing in `wp_options`.
- [x] A tagged value that fails authentication/decryption surfaces as a
      distinct state ("connection needs re-authorising — the site's security
      keys may have changed") on the Google settings panel, not as a blank
      field.
- [x] `tests/Integration/Google/SecretCipherTest.php` extended: round-trip,
      tamper detection (flip one ciphertext byte → decrypt fails safely),
      legacy `enc:` migration, plaintext migration, wrong-key behaviour.
- [x] `docs/GOOGLE.md` documents the key-rotation caveat and the recovery
      path (reconnect).
- [x] `composer qa` green (see decisions log — a pre-existing, unrelated
      test-infra issue was found and worked around for verification, but not
      fixed here; see follow-up ticket 618).

## Out of scope

- A user-supplied key constant (e.g. `CANNYFORGE_ARCHIVE_KEY` in wp-config).
  Worth considering — file a follow-up ticket if 605 review wants it.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — **AEAD choice: `sodium_crypto_secretbox` primary, AES-256-GCM
  fallback.** `sodium` is already confirmed installed in this environment and
  in the CI extension list (`.github/workflows/qa.yml`), and ships with PHP
  ≥ 7.2, so it's the safe default; AES-256-GCM via OpenSSL is the fallback for
  the (rare on modern PHP) host without `sodium`. Both are AEAD, so both
  detect tampering/wrong-key at decrypt time instead of returning garbage.
  New values are tagged `enc2:`; the payload is `base64(algo-byte .
  nonce/iv . [gcm tag] . ciphertext)` so `decrypt()` can tell which backend
  produced a given value without needing two separate tag prefixes.
- 2026-07-21 — **Migration mechanism:** `SecretCipher::decrypt()` gained an
  optional `?callable $on_migrate` parameter (default `null`, fully backward
  compatible with existing one-argument call sites). When a legacy `enc:` or
  untagged-plaintext value is read successfully, `decrypt()` re-encrypts it
  under `enc2:` and invokes the callback with the new value; the three
  callers (`GoogleTokenStore::refresh_token()`/`valid_access_token()`,
  `GoogleSettingsStore::decrypt_secret()`) pass a closure that re-stores it
  via their own `set_option`. This keeps the migration policy in
  `SecretCipher` (one place) while storage stays in the option-store classes.
- 2026-07-21 — **No-backend refusal:** `encrypt()` returns `?string` — `null`
  means "refuses to store" (no AEAD backend at all). Every call site
  (`GoogleTokenStore::save_refresh_token()`/`save_access_token()`,
  `GoogleSettingsStore::save()`) treats `null` as "keep whatever was already
  safely stored, do not persist the new plaintext". `SettingsPage` renders a
  persistent (non-dismissible) `notice-error` whenever
  `SecretCipher::backend_available()` is false, independent of whether a save
  was just attempted, so the condition stays visible until it's fixed. This
  refusal path is not exercisable as a normal PHPUnit test without disabling
  both the `sodium` extension and OpenSSL's AES-256-GCM cipher in-process
  (not practical without `runkit`/a separate process), so it's covered by
  code review + the type contract rather than a dedicated unit test; every
  other required scenario (round-trip, tamper detection, legacy `enc:`
  migration, plaintext migration, wrong-key) is covered in
  `SecretCipherTest`.
- 2026-07-21 — **"Needs re-authorising" status:** computed on read, not
  persisted. `GoogleTokenStore::connection_needs_reauthorising()` returns
  true iff a refresh token is stored (non-empty raw option value) but
  `decrypt()` yields `''` — the only way that happens for a non-empty stored
  value, since `encrypt('')` never produces a tagged ciphertext. `SettingsPage`
  overrides the status passed to the view with the new
  `GoogleTokenStore::STATUS_NEEDS_REAUTH` pseudo-status whenever this is
  true, taking priority over whatever status happens to be persisted (which
  may already have been flipped to `disconnected`/`error` by an unrelated
  `access_token()` call — the on-read check stays correct regardless).
- 2026-07-21 — **Found, not fixed (out of ticket 605 scope):** running the
  full default `composer test`/`vendor/bin/phpunit` invocation on a clean
  `main` checkout (verified via `git stash`, before any 605 changes) silently
  exits the whole PHP process partway through the suite with exit code 0,
  because `tests/bootstrap.php` loads `tests/wp-hooks-shim.php` (defines a
  non-throwing, bool-returning `wp_safe_redirect()`) before
  `tests/wp-admin-post-shim.php` (defines a throwing one, guarded by
  `function_exists`, so it never actually gets defined). Any test whose code
  path calls `wp_safe_redirect(...); exit;` (e.g.
  `GoogleConnectionControllerTest::test_callback_error_with_valid_state_sets_error_status`
  and the three `disconnect()` tests) hits the real, unshimmed `exit;`
  statement and kills the entire PHPUnit process before it reaches the rest
  of the suite (including a pre-existing, independently-failing
  `SettingsViewTest::test_renders_preview_link`, which never runs in a full
  invocation as a result). This makes CI's "PHPUnit" step falsely appear
  green. Filed as ticket 618; not fixed here since it's cross-cutting test
  infrastructure unrelated to `SecretCipher`, shared by at least 3 other
  controllers' tests, and deserves its own design decision rather than an
  incidental fix. Verified this ticket's own changes introduce zero
  regressions by running the full suite with the known-crashing test methods
  filtered out (281/281 passing beyond the 1 pre-existing, unrelated
  failure) and separately confirming all Integration/Google tests pass.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
