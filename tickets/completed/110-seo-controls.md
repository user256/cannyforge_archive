# Ticket 110: SEO controls

**Sprint:** 1 — Settings & MVP
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The plugin is fundamentally an SEO / internal-linking tool, so the archive page
needs basic SEO configuration rather than inheriting whatever the theme emits.
This ticket adds a dedicated SEO settings section and applies it to the archive
page (ticket 103).

## Goal

An SEO settings section whose values drive the archive page's title, meta
description, robots directives, and canonical URL.

## Acceptance criteria

- [x] Settings gain an SEO group: archive title, meta description, index/noindex,
      follow/nofollow, and canonical override (`Contracts\Settings\Seo`).
- [x] The admin settings page renders these under a dedicated "SEO" section and
      persists them via the settings model.
- [x] On the archive page (ticket 103), the configured title, meta description,
      `robots` directive, and canonical are emitted in `<head>` by
      `Frontend\SeoHead` (on `wp_head`, archive request only); an empty canonical
      override falls back to the archive's own URL.
- [x] Robots default is index, follow.
- [x] `composer test` covers the pure `Core\Seo\HeadTagBuilder` for the
      directive combinations (index/follow, noindex/nofollow, mixed), the
      canonical fallback + override, and omission of empty title/description;
      `composer qa` passes.

## Out of scope

- Per-entry SEO (this is the archive page's own SEO).
- Integration with third-party SEO plugins (Yoast, Rank Math) — a possible
  follow-up; for now the plugin owns the archive head output.
- `noindex` of *included content* — that is a content-selection concern
  (ticket 111).

## Dependencies

- **Blocks:** none
- **Blocked by:** 101 (settings model), 102 (admin UI), 103 (archive page to
  apply the head output to)
- **External:** none

## Approach (optional)

Add an `Seo` value object to `Contracts\Settings`, a pure `Core` head-tag
builder that returns the tag set as data (testable without WP), and a thin
`Frontend` hook that echoes it on the archive request only — careful not to
emit duplicate canonical/robots tags on non-archive pages.

## Notes / decisions log

- 2026-06-18 — Created from the confirmed product decisions (SEO section).
- 2026-06-18 — Implemented. Confirmed decision: self-contained, archive page
  only — emit our own tags with a `cannyforge_archive_seo_head` filter for
  override; do NOT detect/defer to Yoast/Rank Math (a noted Sprint-2 follow-up).
  `SeoHead` gates on the archive query var so it never emits duplicate
  robots/canonical on other pages. Bumped the PHPStan `stan` script memory limit
  512M→1G (the growing src tree exceeded the old ceiling in parallel mode; the
  code itself is clean).

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
