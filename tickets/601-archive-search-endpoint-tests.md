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

- [x] `tests/Frontend/ArchiveSearchEndpointTest.php` exists and covers:
      missing/invalid nonce → error response, no query executed;
      valid request → JSON payload shape (`entries`, `total`, `page`,
      `per_page`, pagination metadata) matches the JS contract in
      `assets/js/archive-filters.js`;
      hostile inputs (`per_page=999999`, negative `page`, oversized search
      string, unknown filter keys) are clamped/ignored, never passed raw to
      `ContentIndexProvider`;
      only published/public statuses are ever requested (assert the
      `ContentQuery` handed to the provider).
- [x] The WP shims in `tests/` gain whatever `check_ajax_referer` /
      `wp_send_json_*` doubles are needed, following the existing shim style.
- [x] `composer test` passes; new tests appear in the run count.
- [x] `composer qa` remains green.

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

- 2026-07-21 — `ContentIndexProvider` was `final` with no interface, and its
  `provide()` instantiates `\WP_Query` directly — a class that has no shim in
  `tests/` at all. `createMock()` cannot double a `final` class (verified: it
  throws `ClassIsFinalException`), so the fake described in the ticket's
  approach note wasn't directly buildable as written. Fix: dropped `final`
  from `ContentIndexProvider` (no architecture/deptrac/arch rule depends on
  it) and added `tests/FakeContentIndexProvider.php`, a subclass that
  overrides `provide()` to record the `ContentQuery` and return a canned
  `ContentPage`, leaving `build_query_args()` untouched and reused directly in
  the "only published status" test.
- 2026-07-21 — The "oversized search string is clamped" acceptance criterion
  had no backing behaviour: `ContentQuery` clamped `per_page` but not
  `search` length at all. Since the criterion is explicit and the goal states
  every *security-relevant* behaviour should be pinned, added
  `ContentQuery::MAX_SEARCH_LENGTH = 200` and a `clamp_search()` step
  (mirroring the existing `clamp_per_page()`), plus a direct unit test in
  `ContentQueryTest`, rather than writing a test asserting behaviour that
  didn't exist.
- 2026-07-21 — Added `tests/wp-ajax-shim.php` (`check_ajax_referer`,
  `wp_send_json_success`, `wp_send_json_error`) and `tests/AjaxResponseSpy.php`,
  required from `tests/bootstrap.php` after `wp-admin-post-shim.php`.
  `check_ajax_referer`'s validity is test-controlled via the
  `cannyforge_test_ajax_referer_valid` global (mirrors the existing
  `current_user_can` global) — real `wp_verify_nonce` cryptography is a
  WordPress-runtime concern, explicitly out of scope here (ticket 603).
- 2026-07-21 — **Found, not fixed here (out of scope for this ticket):**
  while verifying `composer qa`, discovered a pre-existing bug (confirmed via
  `git stash` back to a clean `main`, unrelated to this ticket's changes) —
  `wp-hooks-shim.php` and `wp-admin-post-shim.php` both define
  `wp_safe_redirect()`; the first-loaded (non-throwing) one always wins, so
  `GoogleConnectionController::redirect_to_settings()`'s bare
  `wp_safe_redirect(...); exit;` hits a *real* `exit;` mid-test-run. The whole
  PHPUnit process dies silently with exit code 0 after ~8 of the suite's
  tests (`AdminAssetsTest` + the first 5 of `GoogleConnectionControllerTest`,
  in alphabetical run order) — every test after that point, in every file,
  silently never runs, and `composer test`/`qa` reports success anyway. This
  also masked a second, unrelated pre-existing failure in
  `SettingsViewTest::test_renders_preview_link` (never reached because it
  sorts after the file that kills the process). Filed as tickets 618
  (the harness collision) and 619 (the masked test failure). This ticket's
  own new/changed tests were verified green by running `tests/Frontend`,
  `tests/Contracts/Archive`, and every other test directory individually
  (each completes and prints a real summary), since a single default
  `vendor/bin/phpunit` invocation cannot currently be trusted to run past
  that point.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
