# Ticket 110: SEO controls

**Sprint:** 1 — Settings & MVP
**Status:** Not started
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

- [ ] Settings gain an SEO group: archive title, archive meta description,
      index/noindex toggle, follow/nofollow toggle, and a canonical URL override.
- [ ] The admin settings page renders these under a dedicated "SEO" section and
      persists them via the settings model.
- [ ] On the archive page (ticket 103), the configured title, meta description,
      `robots` directives (index/follow), and canonical are emitted in `<head>`;
      an empty canonical override falls back to the archive's own URL.
- [ ] Robots default is index, follow (the archive is meant to be crawled and to
      pass link equity — that is the point of the plugin).
- [ ] `composer test` covers the head-tag builder for the directive
      combinations and the canonical fallback; `composer qa` passes.

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

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
