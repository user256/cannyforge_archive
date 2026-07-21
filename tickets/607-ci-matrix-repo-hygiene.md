# Ticket 607: CI version matrix + repository hygiene

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

CI pins PHP 8.1 only, while `readme.txt` claims "Requires PHP: 8.1" and
"Tested up to: 7.0" (WordPress) — the compatibility claims are untested.
Separately, the working tree carries artefacts a wp.org reviewer (who reads
everything) will notice: a `.env` documenting env vars for a *different*
project ("CannyForge Lead Capture") with an alarming "Firefox Home /
Backdoor" comment, plus `.deptrac.cache` and `.phpunit.result.cache`
committed/archived. None of these ship in `dist/`, but they damage trust in
the source repo and the comment is a genuine liability in any review.

## Goal

Compatibility claims in readme.txt are backed by a CI matrix, and the repo
contains no stray environment files or tool caches.

## Acceptance criteria

- [ ] `qa.yml` runs the unit/static suite across a PHP matrix: 8.1, 8.2,
      8.3, 8.4 (static-analysis gates may pin one version; tests run on all).
- [ ] The integration job (ticket 603) runs against at least the oldest
      supported WP (6.4 per readme.txt) and latest stable.
- [ ] `.env` is deleted (its contents belong to lead-capture, not this repo);
      if this project genuinely needs env documentation, a fresh
      `.env.example` is written for *this* plugin with neutral wording.
- [ ] `.deptrac.cache` and `.phpunit.result.cache` are removed from version
      control and added to `.gitignore`.
- [ ] `readme.txt` "Tested up to" / "Requires PHP" values match what the
      matrix actually exercises.

## Out of scope

- PHPUnit 10/11 and PHPStan 2.x upgrades — file as a Sprint 7 modernisation
  ticket; don't couple toolchain major bumps to this hygiene pass.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none (603's WP matrix criterion activates when 603 merges)
- **External:** none

## Notes / decisions log

- {date} — {decision or finding}

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
