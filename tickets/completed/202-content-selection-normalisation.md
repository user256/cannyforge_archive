# Ticket 202: Content selection normalisation

**Sprint:** 2 — Hardening & Fit
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The current content-selection controls take free-form category/tag input and
match it directly against term display names. That is brittle on live content:
different casing, label punctuation, and slug-vs-name entry all silently miss.
The feature works in unit tests but is too fragile for admin use on real sites.

## Goal

Content-selection matching is predictable and tolerant of realistic admin input.

## Acceptance criteria

- [ ] Category/tag include and exclude matching is normalised at minimum for case and whitespace.
- [ ] The chosen normalisation strategy is documented in the ticket and reflected in tests.
- [ ] The admin UI copy explains what users should enter when the field remains free-form.
- [ ] `vendor/bin/phpunit --testdox` and `vendor/bin/phpstan analyse --no-progress` pass.

## Out of scope

- A full autocomplete / term-picker UI.
- Support for arbitrary taxonomy types beyond the current category/tag scope.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-06-18 — Filed from the Sprint-1 review after seeing exact-name matching in providers + selector.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
