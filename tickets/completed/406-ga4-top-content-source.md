# Ticket 406: GA4 top-content source (optional second Google signal)

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The 403 spike deliberately chose Search Console as the first Google-backed
signal to ship. GA4 remains valuable for broader traffic-based popularity, but
it has extra configuration complexity (property selection and page-dimension
normalisation) and is not required for the first non-empty Google fallback.

## Goal

Add GA4 as an optional second Google popularity source on top of the 404
foundation, without weakening the Search Console-first v1 path.

## Acceptance criteria

- [x] A GA4 Data API client exists behind injected HTTP, querying a configured
      property via `runReport`. (`Ga4Client`.)
- [x] GA4 rows are mapped to clean post-ID candidates with pure PHPUnit-covered
      shaping logic. (`Ga4TopContentRefresher::map_rows_to_post_ids`.)
- [x] The plugin exposes a clear policy for how GA4 is used relative to Search
      Console: explicit source selection or a documented precedence/merge rule.
      (`CompositePopularPostsSource`: strict Search Console → GA4 precedence,
      documented in `docs/GOOGLE.md`.)
- [x] GA4 data uses the same no-page-render HTTP rule as Search Console: cached
      refresh only. (`Ga4CacheStore` + `Ga4RefreshController`; page render reads
      `Ga4CachedPopularPostsSource`, which only reads the cache.)
- [x] Misconfigured or empty GA4 data degrades cleanly to the existing Search
      Console / 402 fallback chain. (Composite falls through unavailable/empty
      members; `is_available()` gates on property ID + connected status.)
- [x] Docs explain the additional Google Cloud configuration for GA4.
      (`docs/GOOGLE.md`.)
- [x] `composer qa` passes.

## Out of scope

- Replacing Search Console as the recommended first source.
- Cross-property auto-discovery or property-picker UX.
- Real-time analytics reporting in the archive UI.

## Dependencies

- **Blocks:** none
- **Blocked by:** 404
- **External:** Connected Google account with GA4 property access

## Approach

- Reuse the OAuth/token foundation from 404.
- Build GA4 as an additive source, not a prerequisite for the Search Console v1
  path chosen in 403.

## Notes / decisions log

- 2026-06-23 — Filed from the 403 spike as an optional follow-on, not the first
  implementation slice.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
