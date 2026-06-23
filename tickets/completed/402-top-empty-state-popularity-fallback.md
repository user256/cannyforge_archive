# Ticket 402: Top/Blog empty-state popularity fallback

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Blog mode (`BlogEntryProvider`) builds the promoted "top content" set purely from
the administrator's curated URL list (manual entry or CSV import — ticket 105).
When that list is empty — a new site, an admin who enabled Blog mode but hasn't
curated yet — the promoted archive renders **zero entries**, the same dead-end
that ticket 401 fixes for News mode.

There is no obvious "top content" signal to fall back on: **WordPress core has no
view/traffic counter.** The only engagement signal in core is `comment_count`.
Ticket 105 deliberately put analytics-driven sourcing (Snowflake/Adobe, automatic
popularity) out of scope; this ticket **partially and intentionally revisits that
stance**, but only for a zero-dependency core signal plus an *optional* Jetpack
Stats read when that plugin is already present. No external analytics
integration, no new credentials. (Full GA4/GSC sourcing is its own follow-on —
ticket 403.)

## Goal

When Blog/Top mode has no curated URLs, the archive falls back to a best-effort
"top content" set chosen by a tiered proxy — most-commented, then Jetpack Stats
views if available, then newest — so the promoted surface is never empty when
content exists.

## Acceptance criteria

- [x] `BlogEntryProvider::provide()` returns the resolved curated set when
      `select_urls()` yields ≥1 URL (unchanged behaviour).
- [x] When `select_urls()` yields 0 URLs, a tiered fallback runs and resolves to
      published post IDs, capped at `blog_max_urls` (default 100), then reuses the
      existing `map_post()` enrichment so output is indistinguishable in shape
      from curated entries:
  - [x] **Tier 1 — comments:** posts ordered by `comment_count` DESC, used only
        if some published post has `comment_count > 0` (`has_commented_post()`
        gate) so an all-zero-comments site does not present an arbitrary order as
        "popular".
  - [x] **Tier 2 — Jetpack Stats:** if Tier 1 is gated off **and** `JetpackStatsSource`
        is available (capability-checked `function_exists('stats_get_csv')`), pull
        top post views and map to post IDs. Entirely skipped — no fatal, no notice
        — when Jetpack is absent.
  - [x] **Tier 3 — newest:** final fallback, latest N published posts newest-first.
- [x] The tier-selection decision is the **pure** `BlogEntryProvider::select_fallback_ids()`,
      covered by PHPUnit (comment gate, each tier's precedence, dedupe + cap).
- [x] Jetpack access is isolated behind the `PopularPostsSource` contract;
      `JetpackStatsSource` is capability-checked with an injected fetcher (unit-
      tested via `map_rows()`), and `NullPopularPostsSource` is the default no-op.
- [x] No new credentials, settings, or external HTTP calls are introduced (Jetpack
      Stats is read in-process via Jetpack's own API).
- [x] Documented in `docs/PLAN.md` (amendment under decision 2) and this ticket
      that this narrows ticket 105 to: core `comment_count` + optional in-process
      Jetpack Stats only.
- [x] `composer qa` passes — 154 tests, 364 assertions, exit 0.
- [x] Verified live at `http://127.0.0.1/archive/` in Blog mode with an empty URL
      list: the archive lists 100 fallback entries instead of 0. The seed data has
      a commented post ("Hello world!", 1 comment) and no Jetpack, so **tier 1
      fired** — the rendered order led with "Hello world!" (most-commented), not
      the newest post, exactly matching the `comment_count DESC` query.

## Out of scope

- GA4 / Google Search Console / any OAuth analytics sourcing — that is **ticket
  403** (the heavier, credential-bearing integration).
- A weighted/blended popularity score across signals — tiers are strict
  precedence, not a composite ranking.
- Hybrid-mode fallback semantics beyond what naturally falls out of the
  News (401) and Blog (402) providers each gaining a fallback.
- Surfacing to the end user which tier produced the list.

## Dependencies

- **Blocks:** none. (403 builds the GA/GSC tier on top of this provider's
  fallback seam.)
- **Blocked by:** none. (Independent of 401; both are empty-state fallbacks.)
- **External:** local WP install for live verification. Jetpack Stats tier cannot
  be live-verified on the current install (Jetpack not present) — covered by unit
  test of the adapter boundary + manual reasoning; note this in the log.

## Approach

- In `BlogEntryProvider`: when `select_urls()` is empty, call a new
  `fallback_post_ids( Settings $settings )` that consults the tiers in order and
  returns up to `blog_max_urls` IDs; map each via the existing post→entry path.
- Keep tier *selection* pure: a method that takes (has-commented-posts flag,
  jetpack-available flag, jetpack-ids, comment-ordered-ids, newest-ids) and
  returns the chosen ID list, unit-tested without WordPress.
- Wrap Jetpack behind a `JetpackStatsSource` (or similar) adapter with an
  `is_available(): bool` + `top_post_ids(int $limit): array`, so the provider
  depends on an interface, not Jetpack directly (keeps deptrac/phparkitect happy
  and the unit test clean).

## Notes / decisions log

- 2026-06-23 — Tier order set by product owner: **comments → Jetpack (if present)
  → newest**. `comment_count > 0` gate added so a comment-less site falls straight
  through to Jetpack/newest instead of presenting an arbitrary order as "popular".
- 2026-06-23 — Reuses `blog_max_urls` as the cap; **no new setting** (per owner,
  the new-setting ask was scoped to the News fallback only — ticket 401).
- 2026-06-23 — Deliberately revisits ticket 105's "no automatic popularity"
  decision, bounded to core `comment_count` + optional in-process Jetpack Stats.
  External analytics (GA4/GSC) remains out of scope here and is filed as 403.
- 2026-06-23 — **Architecture:** popularity is a new `PopularPostsSource` contract
  (Contracts/Archive seam) so Core depends only on the interface — deptrac/
  phparkitect clean. `JetpackStatsSource` (Core) is the real impl, capability-
  gated and injection-testable; `NullPopularPostsSource` (Core) is the default so
  `BlogEntryProvider` stays constructible without args. Bootstrap wires the real
  Jetpack source.
- 2026-06-23 — **Static-analysis notes:** PHPStan can't see Jetpack's optional
  `stats_get_csv` global → `@phpstan-ignore-next-line function.notFound` on the
  guarded call (the only ignore in the codebase, justified by the runtime
  `function_exists` gate). Rector flagged a redundant `(int)` cast on the already-
  int `$id`; removed.
- 2026-06-23 — **Live-verified** stronger than expected: the install's seed data
  includes WordPress's default commented "Hello world!" post, so **tier 1 (most-
  commented) fired** rather than tier 3. The rendered list led with "Hello world!"
  (comments=1), matching the `comment_count DESC` query — proving the comment tier
  and gate, not just the newest floor. Jetpack absent (`stats_get_csv=no`), so
  tier 2 was correctly skipped.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
