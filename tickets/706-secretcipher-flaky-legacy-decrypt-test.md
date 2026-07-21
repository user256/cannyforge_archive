# Ticket 706: Flaky SecretCipherTest wrong-key legacy-decrypt assertion

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

While working ticket 608 (performance at scale, unrelated to `SecretCipher`),
`SecretCipherTest::test_wrong_key_fails_to_decrypt_legacy_format` (added in
ticket 605) was observed failing intermittently — roughly 1 in 4 runs — when
run as part of the full `vendor/bin/phpunit` suite. It always passes when
run in isolation (`vendor/bin/phpunit --filter SecretCipherTest`).
Reproduced on a clean checkout of the ticket 608 worktree's merge base with
none of that ticket's own changes applied (confirmed via `git stash -u` and
rerunning the full suite several times), so this is pre-existing and
unrelated to ticket 608.

The test decrypts a legacy (`enc:`-prefixed, unauthenticated AES-256-CBC)
value with the wrong key and currently asserts the result equals `''`
(`assertSame('', ...)`). Because the legacy format has no authentication tag,
decrypting with the wrong key produces essentially random bytes — most of the
time OpenSSL's PKCS7 unpad correctly rejects that as invalid padding
(yielding `''`), but by chance (~1 in 256 per trial, given a single
plaintext-length byte determines the unpad validity check) the random bytes
can look like valid padding and the function returns non-empty garbage
instead, failing the `assertSame('', ...)` assertion.

## Goal

`SecretCipherTest::test_wrong_key_fails_to_decrypt_legacy_format` passes
deterministically regardless of run order or which other tests ran first —
no reliance on a fixed low-probability event happening to land favourably.

## Acceptance criteria

- [ ] The test's assertion no longer depends on the wrong-key decrypt
      output happening to equal `''`. Two viable approaches (pick one, record
      the choice): (a) assert the decrypted value is never the *original
      plaintext* (`assertNotSame($original_plaintext, $decrypted)`) — true
      regardless of whether PKCS7 unpad coincidentally validates, since wrong
      key can never reproduce the correct plaintext; or (b) run enough
      independent trials (fresh IV per trial) that the probability of every
      trial coincidentally validating is negligible, and assert per-trial
      that the output is not the original plaintext rather than not empty.
- [ ] Run `vendor/bin/phpunit` at least 20 times in a loop with no failures
      from this test, to build confidence the flake is actually resolved
      (not just made less likely).
- [ ] `composer qa` green.

## Out of scope

- Any other `SecretCipher`/ticket 605 behaviour — this is purely a test
  determinism fix, not a change to `SecretCipher` itself.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-21 — Filed from ticket 608 after noticing this flake while
  verifying `composer qa` was reliably green for unrelated changes. As of
  the ticket 608 worktree's base commit, the test still uses the flaky
  `assertSame('', ...)` check — if a fix has already landed on `main` by the
  time this is picked up, close this ticket as already resolved rather than
  duplicating the work.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
