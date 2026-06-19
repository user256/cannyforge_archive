# Ticket 206: Fragment caching via transients for HTML sitemap

**Sprint:** 2 — Hardening & fit
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Currently, `ArchiveRenderer.php` builds the archive HTML synchronously on every request by looping through every entry returned by the provider. For sites with hundreds or thousands of posts, running this on every archive page view consumes significant CPU and delays time-to-first-byte (TTFB). This ticket aims to cache the final output to improve performance at scale.

## Goal

The plugin should cache the rendered HTML archive list using the WordPress Transients API, serving the cached version on subsequent requests and invalidating it only when settings change or post content changes.

## Acceptance criteria

- [ ] `src/Frontend/ArchivePage.php` checks for a cached transient before querying the entry provider and rendering HTML.
- [ ] Rendered HTML is cached with `set_transient()` using a stable key (e.g. hashed from settings or a static key).
- [ ] Transients are correctly invalidated/cleared when a post is published, updated, or deleted.
- [ ] Transients are correctly invalidated/cleared when the CannyForge Archive settings are saved.
- [ ] Unit tests are added/updated to verify cache hits, misses, and invalidation behaviour.

## Out of scope

- Caching the underlying WordPress queries directly (the entry providers). We are focusing on fragment caching the final HTML only.
- Client-side browser caching mechanisms or integrating with third-party page caching plugins (e.g., WP Rocket).

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Approach (optional)

Implement a `CacheInvalidator` class that hooks into `save_post`, `deleted_post`, and the plugin's settings save hook. This class should manage the clearing of the transient. Wrap the render call in `ArchivePage.php` with a check for `get_transient()`.

## Notes / decisions log

- 

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
