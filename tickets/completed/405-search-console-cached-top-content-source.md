# Ticket 405: Search Console cached top-content source for Blog fallback

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

Ticket 404 provides the Google OAuth and secure-settings foundation. The 403
spike chose Search Console as the first genuine Google signal to ship for Blog
mode because it fits the plugin's SEO/internal-linking purpose better than GA4
and is easier to map cleanly to post URLs.

## Goal

When Blog mode's curated URL list is empty, the archive can use cached Search
Console top-content IDs before falling back to comments, Jetpack, and newest.

## Acceptance criteria

- [ ] A Search Console client queries the configured site property via injected
      HTTP and parses top-page rows for a configurable date window.
- [ ] Row→post mapping is isolated and pure: Search Console page URLs are mapped
      through `url_to_postid()`, deduplicated, filtered to positive published
      post IDs, and PHPUnit-covered.
- [ ] Google fetches happen only in a refresh path (manual action and/or cron),
      never during archive page render.
- [ ] Cached Search Console IDs are exposed through a `PopularPostsSource`
      implementation that page render can read without performing HTTP.
- [ ] `BlogEntryProvider` is refactored so fallback precedence becomes:
      Google cached IDs → commented posts → Jetpack Stats → newest.
- [ ] If Google is disconnected, misconfigured, empty, or stale, the archive
      still degrades cleanly through the 402 proxy tiers without rendering blank.
- [ ] A manual `Refresh now` action exists for admin verification/debugging.
- [ ] `composer qa` passes.

## Out of scope

- GA4 support.
- Automatic discovery of Search Console properties.
- Per-request Google API calls.

## Dependencies

- **Blocks:** none
- **Blocked by:** 404
- **External:** Connected Google account with Search Console access to the site

## Approach

- Add a cached Search Console source in `src/Integration/Google/`.
- Store mapped post IDs in a transient or dedicated option keyed to the archive
  plugin.
- Update `BlogEntryProvider`'s pure precedence method so Google becomes the top
  tier without weakening the comment-count gate from ticket 402.

## Notes / decisions log

- 2026-06-23 — Filed from the 403 spike as the recommended v1 shipping slice for
  genuine Google-sourced popularity.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
