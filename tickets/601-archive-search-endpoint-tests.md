# Ticket 601: Unit tests for ArchiveSearchEndpoint (the public attack surface)

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

`src/Frontend/ArchiveSearchEndpoint.php` is the plugin's only public,
unauthenticated (`nopriv`) endpoint — and it has no test file. Every other
Frontend class does. The endpoint's own docblock promises nonce binding,
input sanitisation, and public-content-only exposure, but none of those
promises are pinned by a test, so any refactor can silently break them.
The `per_page` clamp lives in `ContentQuery` (`MAX_PER_PAGE = 100`) and *is*
tested, but the request-parsing path in the endpoint (`$_REQUEST` handling,
defaults, nonce failure behaviour) is not.

## Goal

Every security- and correctness-relevant behaviour of `ArchiveSearchEndpoint`
is pinned by a unit test that fails if the behaviour regresses.

## Acceptance criteria

- [ ] `tests/Frontend/ArchiveSearchEndpointTest.php` exists and covers:
      missing/invalid nonce → error response, no query executed;
      valid request → JSON payload shape (`entries`, `total`, `page`,
      `per_page`, pagination metadata) matches the JS contract in
      `assets/js/archive-filters.js`;
      hostile inputs (`per_page=999999`, negative `page`, oversized search
      string, unknown filter keys) are clamped/ignored, never passed raw to
      `ContentIndexProvider`;
      only published/public statuses are ever requested (assert the
      `ContentQuery` handed to the provider).
- [ ] The WP shims in `tests/` gain whatever `check_ajax_referer` /
      `wp_send_json_*` doubles are needed, following the existing shim style.
- [ ] `composer test` passes; new tests appear in the run count.
- [ ] `composer qa` remains green.

## Out of scope

- Integration testing against real WordPress (ticket 603).
- Rate limiting the endpoint (ticket 608).

## Dependencies

- **Blocks:** 604 (mutation threshold is set after coverage tickets land)
- **Blocked by:** none
- **External:** none

## Approach (optional)

Follow the pattern in `tests/Frontend/PaginationControllerTest.php`: construct
the endpoint with the in-memory `OptionStore`-backed repository and a fake
`ContentIndexProvider` that records the `ContentQuery` it receives, then
assert on the recorded query rather than on WP_Query internals.

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
