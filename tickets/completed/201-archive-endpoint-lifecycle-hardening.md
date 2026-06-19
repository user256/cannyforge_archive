# Ticket 201: Archive endpoint lifecycle hardening

**Sprint:** 2 — Hardening & Live-site polish
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Sprint 1 proved the archive endpoint works on a live WordPress install, but the
review also surfaced two lifecycle gaps around the endpoint itself. The current
rewrite endpoint is registered, but activation/deactivation does not manage
rewrite flushing, and the archive request gate is broad enough to risk serving
duplicate content on non-canonical `/archive/*` paths. Those are operational
issues that won’t show up in the pure unit suite unless we intentionally cover
them.

## Goal

The archive endpoint installs cleanly, resolves reliably after activation, and
serves only its canonical route.

## Acceptance criteria

- [ ] Plugin activation registers the archive endpoint and flushes rewrite rules once; deactivation flushes them again.
- [ ] The archive controller renders only for the canonical endpoint request and redirects or rejects non-canonical endpoint tails.
- [ ] Automated coverage is added for the endpoint gating / lifecycle decisions that can be tested in the local suite.
- [ ] Docs or ticket notes record the intended activation/deactivation behaviour for live installs.

## Out of scope

- Changing the archive slug to become user-configurable.
- Broader SEO changes unrelated to endpoint lifecycle.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** a live or staging WordPress install for final behavioural confirmation

## Notes / decisions log

- 2026-06-18 — Filed from code review after Sprint 1; current endpoint works but has deployment/canonical hardening gaps.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
