# Ticket 607: CI version matrix + repository hygiene

**Sprint:** 6 — Trust & Scale
**Status:** In review
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

- [x] `qa.yml` runs the unit/static suite across a PHP matrix: 8.1, 8.2,
      8.3, 8.4 (static-analysis gates may pin one version; tests run on all).
- [ ] The integration job (ticket 603) runs against at least the oldest
      supported WP (6.4 per readme.txt) and latest stable. **Blocked by
      603, which hasn't merged yet** — see decisions log; this criterion
      activates once 603 lands and cannot be completed from this ticket
      alone.
- [x] `.env` is deleted (its contents belong to lead-capture, not this repo);
      if this project genuinely needs env documentation, a fresh
      `.env.example` is written for *this* plugin with neutral wording.
- [x] `.deptrac.cache` and `.phpunit.result.cache` are removed from version
      control and added to `.gitignore`.
- [x] `readme.txt` "Tested up to" / "Requires PHP" values match what the
      matrix actually exercises.

## Out of scope

- PHPUnit 10/11 and PHPStan 2.x upgrades — file as a Sprint 7 modernisation
  ticket; don't couple toolchain major bumps to this hygiene pass.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none (603's WP matrix criterion activates when 603 merges)
- **External:** none

## Notes / decisions log

- 2026-07-21 — `.env`, `.deptrac.cache`, and `.phpunit.result.cache` were
  never tracked in git (`git ls-files` confirms none are committed) and
  `.gitignore` already lists all three — the "committed/archived artefacts"
  framing in this ticket's Context section is stale. The real, still-valid
  problem was a stray untracked `.env` physically sitting in the working
  tree, documenting env vars for a different plugin ("CannyForge Lead
  Capture") with an unrelated Kanban-app comment. Deleted it. Confirmed (via
  grep for `getenv`/`putenv`/`$_ENV`) that this plugin's own scripts
  (`scripts/install-plugin.sh`, `scripts/seed-historic-content.sh`) don't
  use any environment variables, so no `.env.example` was written — there's
  nothing of this plugin's own to document.
- 2026-07-21 — `qa.yml` split into three jobs: `static-analysis` (cs, stan,
  rector, arch, deptrac, mess — pinned to PHP 8.1, per the ticket's explicit
  allowance), `test` (PHPUnit, matrixed across 8.1/8.2/8.3/8.4,
  `fail-fast: false`; Infection mutation testing still runs report-only, but
  only on the 8.1 leg, since it's expensive and not expected to vary
  meaningfully by PHP version), and the pre-existing `js-tests` job
  (unchanged). `composer install` was verified locally to work unmodified
  under PHP 8.3 despite `composer.json`'s `config.platform.php: "8.1"` pin
  (that pin only affects Composer's own dependency-resolution view, not the
  real interpreter tests run under), so no `--ignore-platform-reqs` flag was
  needed.
- 2026-07-21 — `readme.txt`'s `Requires PHP: 8.1` header is a wp.org
  minimum-version floor, not a range — there's no equivalent "tested up to"
  field for PHP the way there is for WordPress, and writing a range into
  that header would break WordPress's own `version_compare()`-based
  admin-notice parsing. Left the header as `8.1` (still accurate — it's the
  matrix's floor) and added a sentence to the Description explaining CI now
  verifies 8.1 through 8.4, so the compatibility claim is backed rather than
  asserted. The WordPress "Tested up to: 7.0" line was left untouched, as
  instructed — that axis is 603's/the integration job's responsibility.
  Nothing added here contradicts it.
- 2026-07-21 — **`composer qa`'s "green" result needs an important caveat.**
  While confirming the quality gate for this ticket, discovered that
  `composer test` (and therefore `composer qa`) currently exits `0` without
  running roughly 44 of 50 test files (~240+ tests), due to a pre-existing
  bug unrelated to this ticket's changes: a duplicate, non-throwing
  `wp_safe_redirect()` test shim (`tests/wp-hooks-shim.php`, loaded before
  `tests/wp-admin-post-shim.php` in `tests/bootstrap.php`) causes a real
  PHP `exit;` in `GoogleConnectionController::redirect_to_settings()` to
  actually terminate the PHPUnit *process* mid-suite (with a clean/misleading
  exit code 0) the first time a test exercises that path. Verified this by
  reproducing outside PHPUnit entirely (a standalone PHP script hits the
  same silent exit) and by running each `tests/` subdirectory individually:
  `Bootstrap`, `Contracts`, `Core`, `Frontend`, `Integration`, and
  `Packaging` all genuinely pass in full (237 tests). Only `Admin/` is
  affected — and once past the collision, `SettingsViewTest::test_renders_preview_link`
  turns out to be a real, currently-masked failure (looks like leftover
  wording from ticket 613 that was never reconciled with this test). None of
  this is caused by or in scope for 607 — filed as ticket 619, plus ticket
  618 for the explicitly out-of-scope PHPUnit 10/11 / PHPStan 2.x bump. For
  607's own gate: `composer cs`, `composer stan`, `composer rector`,
  `composer arch`, `composer deptrac`, and `composer mess` all pass cleanly
  standalone (confirmed individually), and every `tests/` subdirectory
  except `Admin/` passes in full when run in isolation — that's the
  strongest honest claim available until 619 unblocks a real whole-suite
  run.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
