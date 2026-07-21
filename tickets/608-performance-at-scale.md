# Ticket 608: Performance at scale — bound the unbounded query, cache the search endpoint, rate-limit nopriv

**Sprint:** 6 — Trust & Scale
**Status:** Complete except the live-instance benchmark (substituted — see decisions log)
**Owner:** unassigned
**Estimate:** M

---

## Context

The plugin's pitch is large news sites — exactly the sites where three
current behaviours hurt:

1. `Core/Archive/ContentIndexProvider` has a `posts_per_page = -1` path
   (used for filter-option/total derivation) — an unbounded `WP_Query` that
   loads every matching post on a 100k-post site.
2. The whole-DB search endpoint runs an uncached `WP_Query` per request. The
   HTML-sitemap fragment is transient-cached (ticket 206) but search results
   are not, and search is the surface bots and users will hammer.
3. The endpoint is `nopriv` with a nonce but no throttle; a scripted client
   with a scraped nonce can generate arbitrary query load.

## Goal

No code path issues an unbounded query, hot search responses are served from
cache, and the public endpoint has a basic abuse ceiling — all verified on a
seeded large dataset.

## Acceptance criteria

- [x] The `posts_per_page = -1` path is replaced: `fields => 'ids'` plus
      batched paging, or a direct bounded query — decide and record. A guard
      test asserts no `ContentQuery`/args array with `-1` reaches `WP_Query`.
- [x] Search endpoint responses are cached (transient or object cache) keyed
      on the normalised query params, with invalidation wired into the
      existing `CacheInvalidator` on publish/update/delete.
- [x] A lightweight per-IP throttle on the nopriv action (e.g. transient
      counter, N requests / minute, filterable via a `cannyforge_archive_*`
      hook per docs/HOOKS.md conventions) returns 429-style JSON when hit.
- [ ] Benchmark recorded in the decisions log: seed ≥ 20k posts via
      `scripts/seed-historic-content.sh --count 20000` (extend the script if
      needed), measure archive-page render and cold/warm search response
      before and after. **Not done as specified** — no ticket-isolated live
      WordPress instance was available in this sandbox (see decisions log);
      substituted a query-building-logic timing/scaling comparison instead,
      which the ticket explicitly allows as a fallback. Left unchecked
      because the live-seeded measurement itself genuinely wasn't performed.
- [x] `composer qa` green; new behaviour unit-tested (cache hit path, cache
      invalidation, throttle trip and reset).

## Out of scope

- Migrating admin-ajax → REST API. Worth a Sprint 7 ticket (REST gets you
  proper HTTP caching and cleaner routing), but it's a contract change for
  `archive-filters.js` and shouldn't ride along here.
- Elasticsearch/external search integrations.

## Dependencies

- **Blocks:** none
- **Blocked by:** 601 (endpoint tests exist first, so this refactor has a net)
- **External:** none

## Notes / decisions log

- 2026-07-21 — **Bounded-query decision.** Replaced `ContentIndexProvider::count()`'s
  `posts_per_page => -1` (materialise every matching post ID) with a new
  `build_count_args()` that requests exactly one row (`posts_per_page => 1`,
  `fields => 'ids'`) and reads the total from `$wp_query->found_posts`
  instead of `count($wp_query->posts)`. `no_found_rows` is deliberately left
  at its WordPress default of `false` so that calculation still runs —
  WordPress computes `found_posts` via `SQL_CALC_FOUND_ROWS`/a `COUNT(*)`
  query regardless of the `LIMIT`, so the total comes back exact without ever
  transferring/materialising more than one row into PHP. This was chosen over
  batched paging because it's simpler (one query, not N), gives an exact
  total in one round trip, and directly targets the actual cost driver (an
  unbounded PHP-side array proportional to match count), rather than trading
  it for several bounded-but-still-numerous round trips. Guarded by
  `ContentIndexProviderTest::test_build_query_args_never_contains_unbounded_value()`
  and `::test_build_count_args_never_contains_unbounded_value()`, which
  recursively scan the args array for a literal `-1` across a spread of
  inputs including hostile paging values.

- 2026-07-21 — **Cache-key strategy.** Added `Core/Cache/SearchResultCache`,
  following the `ArchiveCache` (ticket 206) transient pattern but with a
  per-query key rather than one fixed key per archive mode: the key is
  `cannyforge_archive_search_{generation}_{md5(normalised params)}`, built
  from `ContentQuery`'s already-clamped/trimmed search/category/tag/author/
  month/page/per_page accessors. Because a search response's key space is
  effectively unbounded (unlike `ArchiveCache`'s fixed small set of archive
  modes) and WordPress transients have no wildcard delete, invalidation can't
  enumerate and delete every previously-written key. Instead `clear()`
  advances a small `cannyforge_archive_search_cache_generation` option;
  every key embeds the current generation, so bumping it instantly orphans
  every previously-cached response without needing to know their keys.
  Orphaned entries expire via their own TTL (1 hour — shorter than
  `ArchiveCache`'s 24h, since search draws far more distinct one-off/bot
  query keys that would otherwise linger). `CacheInvalidator` now takes an
  optional second `SearchResultCache` collaborator and clears both caches on
  every hook it already listens to (`save_post`, `deleted_post`,
  `cannyforge_archive_settings_saved`, term and profile hooks) — no new hooks
  needed, since the search cache is invalidated by the exact same events as
  the HTML cache (both embed post/term/author data).

- 2026-07-21 — **Throttle design.** Added `Core/RateLimit/SearchThrottle`: a
  fixed-window per-IP transient counter (`REMOTE_ADDR` only — no
  `X-Forwarded-For` trust, since that's spoofable without a host-specific
  trusted-proxy setup). Default 30 requests per 60-second window, both
  filterable (`cannyforge_archive_search_throttle_limit` /
  `cannyforge_archive_search_throttle_window`, documented in docs/HOOKS.md).
  On trip, `ArchiveSearchEndpoint::handle()` responds with
  `wp_send_json_error(['message' => ...], 429)` before the nonce-verified
  request ever reaches the query/cache layer. Chosen fixed-window over
  sliding-window for simplicity (one transient read/write per request); the
  accepted trade-off is a client can burst up to ~2x the limit across a
  window boundary. An IP that can't be resolved (empty string) fails open
  rather than throttling every such request under one shared bucket.

- 2026-07-21 — **Benchmark: what was actually done, and why not the full spec.**
  This sandbox has exactly one live WordPress instance (`/var/www/html`,
  `cannyforge-archive` plugin active, ~1,022 posts already present) and it is
  shared, ambient infrastructure — `git worktree list` shows several other
  agents actively working in parallel worktrees on other Sprint 6 tickets
  right now, all of which would see the same filesystem path. Seeding it with
  `scripts/seed-historic-content.sh --count 20000` (the script already
  supports `--count`; no extension was needed) or installing this
  in-progress branch's build into it would mutate a resource none of those
  other agents expect to change mid-task, and I did not have exclusive
  access to it. There was no ticket-isolated instance to use instead
  (ticket 603's real-WordPress integration rig, which would provide exactly
  that, is still in progress in another worktree as of this branch's base —
  see the follow-up ticket below for using it once it lands). So: no
  page-render or live cold/warm search HTTP timings are reported here —
  reporting any would mean fabricating numbers against a site this change
  was never actually run on.

  Substituted the query-building-logic timing/scaling comparison the ticket
  explicitly allows as a fallback: `scripts/benchmark-bounded-query.php`
  (standalone, no WordPress/DB dependency) simulates the PHP-side
  materialisation both the old and new `count()` paths do — old: build an
  N-element array of post IDs (`posts_per_page => -1`); new: build a
  1-element array (`posts_per_page => 1`, reading `found_posts` for the
  total). Actual output from `php scripts/benchmark-bounded-query.php` on
  this machine:

  ```
     Matches |     Unbounded ms Unbounded KB |       Bounded ms   Bounded KB |    Speedup
  ------------------------------------------------------------------------------------------
        1000 |           0.0221         20.1 |           0.0005          0.0 |     48.6x
       10000 |           0.2165        260.1 |           0.0003          0.0 |    698.5x
       20000 |           0.3633        516.1 |           0.0001          0.0 |   2438.1x
       50000 |           1.8886       1028.1 |           0.0009          0.0 |   1998.5x
      100000 |           3.1151       2052.1 |           0.0007          0.0 |   4450.1x
  ```

  This confirms the old path's PHP-side cost (time and memory) scales
  linearly with the number of matching posts (visible in the KB column: ~20
  bytes/match, so ~2MB just for the ID array at 100k matches, on top of
  whatever WordPress/MySQL allocate for the row transfer itself), while the
  new path's cost is flat regardless of scale. It does **not** measure the
  MySQL-side query cost difference (both paths still need an index/full scan
  to compute the total; the new path only avoids transferring and
  materialising every matching row on top of that) — that requires the live
  render/cold-warm-search measurement this ticket asked for, which is the
  genuinely-unperformed part noted above. Filed as follow-up: see ticket 705.

- 2026-07-21 — **Pre-existing flaky test found, unrelated to this ticket.**
  `SecretCipherTest::test_wrong_key_fails_to_decrypt_legacy_format` (ticket
  605) fails intermittently (~1 in 4 runs observed) when run as part of the
  full suite, always passes in isolation, and reproduces on a clean `main`
  checkout with none of this ticket's changes applied — confirmed by
  stashing all changes and rerunning `vendor/bin/phpunit` several times
  against the unmodified merge base. As of this branch's base commit, the
  test still asserts `assertSame('', ...)` on a decrypted-with-wrong-key
  legacy value, which is what's flaky (unauthenticated legacy AES-256-CBC
  occasionally PKCS7-unpads "successfully" by chance). Not touched here
  (out of scope for performance work); filed as ticket 706.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
