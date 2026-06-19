# Ticket 205: Historic-content seeding + archive smoke data

**Sprint:** 2 — Hardening & Fit
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The archive concept only becomes meaningful with deep, old content, but the
repo currently has no repeatable way to seed a WordPress test site with enough
historic posts to exercise pagination depth and archive filtering/search
behavior. Manual seeding is slow and inconsistent across installs.

## Goal

A repeatable seeding command creates realistic historic content for archive and
pagination smoke testing.

## Acceptance criteria

- [x] A repo-native helper can seed a WordPress install with a sizeable set of old posts spread across historical dates.
- [x] Seeded posts include enough metadata variation to exercise archive search/filter behavior.
- [x] The seeding flow is documented with any environment assumptions.
- [x] A basic smoke checklist for archive/filter testing is recorded.

## Out of scope

- Performance benchmarking at production scale.
- Browser-test automation for every interactive path.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** a reachable WordPress install with WP-CLI available.

## Notes / decisions log

- 2026-06-18 — Filed from the request to add fake old content so archive behavior can be tested with historical data.
- 2026-06-18 — Implemented via `scripts/seed-historic-content.sh`, Composer/README docs, and live smoke against `http://127.0.0.1/archive/`.
- 2026-06-18 — Corrected the smoke language: archive filtering in this plugin is client-side JavaScript, not AJAX.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
