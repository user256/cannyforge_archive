# Ticket 605: Harden SecretCipher — authenticated encryption, fail-loud fallbacks, key rotation

**Sprint:** 6 — Trust & Scale
**Status:** Not started
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

- [ ] Encryption uses an AEAD: `sodium_crypto_secretbox` (ext-sodium ships
      with PHP ≥ 7.2 and is already in the CI extension list) or AES-256-GCM;
      new values are written under a new tag (e.g. `enc2:`).
- [ ] Decrypt reads both `enc2:` and legacy `enc:`; on the first successful
      legacy read, the value is re-encrypted and re-stored under `enc2:`
      (opportunistic migration). Same for legacy plaintext values.
- [ ] If no AEAD backend is available, `encrypt()` refuses to store the
      secret and the admin sees a persistent notice explaining why, instead
      of plaintext landing in `wp_options`.
- [ ] A tagged value that fails authentication/decryption surfaces as a
      distinct state ("connection needs re-authorising — the site's security
      keys may have changed") on the Google settings panel, not as a blank
      field.
- [ ] `tests/Integration/Google/SecretCipherTest.php` extended: round-trip,
      tamper detection (flip one ciphertext byte → decrypt fails safely),
      legacy `enc:` migration, plaintext migration, wrong-key behaviour.
- [ ] `docs/GOOGLE.md` documents the key-rotation caveat and the recovery
      path (reconnect).
- [ ] `composer qa` green.

## Out of scope

- A user-supplied key constant (e.g. `CANNYFORGE_ARCHIVE_KEY` in wp-config).
  Worth considering — file a follow-up ticket if 605 review wants it.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
