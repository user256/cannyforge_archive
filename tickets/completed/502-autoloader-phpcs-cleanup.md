# Ticket 502: Autoloader PHPCS cleanup

**Sprint:** 5 — wp.org submission compliance
**Status:** Done
**Owner:** user256
**Estimate:** S

---

## Context

The follow-up WordPress.org audit after ticket 501 found one remaining local QA
warning in the shipped plugin: the runtime autoloader closure used `$class` as a
parameter name, which PHPCS flags as a reserved-keyword identifier. This is not
expected to block review, but leaving avoidable QA noise in the distributable
build makes the release harder to verify cleanly.

## Goal

The runtime autoloader passes the repo's PHPCS rules cleanly in both source and
shipping artifacts.

## Acceptance criteria

- [x] The plugin-local autoloader uses a non-reserved parameter name in the repo root source file.
- [x] The staged distributable copy under `dist/cannyforge-archive/` contains the same fix.
- [x] `vendor/bin/phpcs dist/cannyforge-archive --standard=phpcs.xml.dist --report=full` passes with no warnings.
- [x] `dist/cannyforge-archive-0.1.1.zip` is rebuilt so the shipped ZIP contains the fix.

## Out of scope

- Changing plugin behaviour or autoloading strategy.
- Any broader WordPress.org naming, trademark, or service-disclosure review work already covered by ticket 501.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Approach

Rename the closure parameter in the tiny PSR-4 autoloader, mirror the change
into the staged dist tree, then rebuild and re-run PHPCS against the shipping
artifact.

## Notes / decisions log

- 2026-06-25 — Chose `$fqcn` as the replacement identifier because it is short, precise, and avoids reserved-keyword warnings.
- 2026-06-25 — Rebuilt the checked-in ZIP after the source patch so the audit applies to the actual shipping artifact, not only the repo tree.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
