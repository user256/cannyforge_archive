# Ticket 199: Sprint 1 review gate (Go / No-Go)

**Sprint:** 1 — Settings & MVP
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

The mandatory `N99` sprint review gate. Sprint 1 is the MVP from the brief; this
ticket verifies the whole thing hangs together end-to-end before the sprint is
declared done and the next sprint is planned.

## Goal

A Go/No-Go decision on Sprint 1, backed by a full end-to-end run of the MVP.

## Acceptance criteria

- [ ] Tickets 101–111 are all Done (or explicitly deferred with a filed
      follow-up ticket).
- [ ] `composer qa` passes on a clean checkout.
- [ ] Manual end-to-end smoke on a WordPress install: configure both Blog and
      News mode, generate the archive, confirm the link-type toggles and each
      enabled filter work, and confirm the shortened pagination links to the
      archive — on the targeted archive types only (109).
- [ ] SEO output verified: the archive emits the configured title, meta
      description, robots directives, and canonical (110).
- [ ] Content selection verified: include/exclude, noindex dropping, and pinned
      ordering take effect in both modes (111).
- [ ] The crawlable (no-JS) archive output is verified as valid HTML.
- [ ] `tickets/overview.md` programme-status table updated; Go/No-Go recorded in
      the notes log below.

## Out of scope

- Implementing any deferred feature — those become Sprint 2 tickets.

## Dependencies

- **Blocks:** Sprint 2 planning
- **Blocked by:** 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111
- **External:** a test WordPress install.

## Notes / decisions log

- {date} — Go / No-Go: {decision}

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
