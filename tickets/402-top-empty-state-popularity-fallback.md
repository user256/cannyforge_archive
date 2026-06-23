# Ticket 402: Top/Blog empty-state popularity fallback

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Not started
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

- [ ] `BlogEntryProvider::provide()` returns the resolved curated set when
      `select_urls()` yields ≥1 URL (unchanged behaviour).
- [ ] When `select_urls()` yields 0 URLs, a tiered fallback runs and resolves to
      published post IDs, capped at `blog_max_urls` (default 100), then reuses the
      existing `map_post()` enrichment so output is indistinguishable in shape
      from curated entries:
  - [ ] **Tier 1 — comments:** posts ordered by `comment_count` DESC, used only
        if the top result has `comment_count > 0` (so an all-zero-comments site
        does not produce an arbitrary order masquerading as "popular").
  - [ ] **Tier 2 — Jetpack Stats:** if Tier 1 is empty/unavailable **and** Jetpack
        Stats is detectably present (capability-checked, e.g.
        `function_exists('stats_get_csv')` or the Jetpack Stats package), pull top
        post views and map to post IDs. Entirely skipped — no fatal, no notice —
        when Jetpack is absent.
  - [ ] **Tier 3 — newest:** final fallback, latest N published posts newest-first.
- [ ] The tier-selection decision (which tier wins given the available signals) is
      expressed as a **pure**, WordPress-free method covered by PHPUnit — at
      minimum the "comment_count > 0 gate" and the tier-precedence logic.
- [ ] Jetpack access is isolated behind a thin, capability-checked adapter so the
      provider stays unit-testable and the Jetpack call is a no-op when the plugin
      is missing.
- [ ] No new credentials, settings, or external HTTP calls are introduced (Jetpack
      Stats is read in-process via Jetpack's own API).
- [ ] Documented in `docs/PLAN.md` (and this ticket) that this deliberately
      narrows ticket 105's "no automatic popularity" decision to: core
      `comment_count` + optional in-process Jetpack Stats only.
- [ ] `composer qa` passes.
- [ ] Verified live at `http://127.0.0.1/archive/` in Blog mode with an empty URL
      list: the archive lists a non-empty fallback set (newest, on this install,
      since the seed data has no comments and no Jetpack), after flushing the
      `cannyforge_archive_html_blog` transient.

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

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
