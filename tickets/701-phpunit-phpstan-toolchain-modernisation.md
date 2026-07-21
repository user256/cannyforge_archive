# Ticket 701: PHPUnit 10/11 and PHPStan 2.x toolchain modernisation

**Sprint:** 7 — Modernisation (proposed)
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Ticket 607 (CI version matrix + repo hygiene) added a PHP 8.1–8.4 test
matrix but deliberately left the dev toolchain versions untouched:
`phpunit/phpunit` is pinned to `^9.6` and `phpstan/phpstan` to `^1.12` in
`composer.json`. PHPUnit 9 is EOL-adjacent and doesn't have first-class
PHP 8.4 support the way 10/11 do; PHPStan 1.x lacks level 10 and the
`@phpstan-pure`/list-type improvements in 2.x (the `composer stan` output
already nags about this on every run: "PHPStan 2.x is available"). Coupling
a toolchain major-version bump to a hygiene/CI-matrix ticket was explicitly
called out as scope creep in 607, so it's filed here instead.

## Goal

The dev toolchain (`phpunit/phpunit`, `phpstan/phpstan`, and any directly
coupled packages such as `szepeviktor/phpstan-wordpress`) is upgraded to a
current major version with `composer qa` green across the full PHP 8.1–8.4
matrix introduced in ticket 607.

## Acceptance criteria

- [ ] `phpunit/phpunit` upgraded to `^10.0` or `^11.0` (whichever has the
      better WordPress-ecosystem compatibility story at implementation
      time); `phpunit.xml.dist` migrated to the new schema/config keys.
- [ ] `phpstan/phpstan` upgraded to `^2.0`; `phpstan-wordpress` and
      `phpstan-deprecation-rules` bumped to compatible releases; the
      "PHPStan 2.x is available" nag is gone from `composer stan` output.
- [ ] `composer qa` (cs/stan/rector/arch/deptrac/mess/test) is green on all
      four matrix legs (PHP 8.1, 8.2, 8.3, 8.4) in CI, not just locally.
- [ ] Any PHPUnit 10/11 breaking changes actually hit by this codebase
      (e.g. removed/renamed assertions, `void` return typing on
      `setUp`/`tearDown`, changed CLI flags used in `composer.json` scripts
      or `qa.yml`) are fixed, not worked around with compatibility shims.
- [ ] `composer.json`'s `config.platform.php` pin and any PHP-version-gated
      code paths are re-checked against the new toolchain's own minimum PHP
      requirement (PHPUnit 10/11 and PHPStan 2.x may raise their own PHP
      floors above this plugin's 8.1).

## Out of scope

- Changing the plugin's own `Requires PHP` floor (still 8.1, per readme.txt
  and composer.json `require.php`) — this ticket only touches `require-dev`.
- Any other Sprint 6 test-suite defects; see the resolved
  [618 — PHPUnit shim collision](completed/618-phpunit-shim-collision-silently-truncated-suite.md)
  for a specific, independent test-infrastructure bug discovered while
  validating 607 (already fixed on `main`).

## Dependencies

- **Blocks:** none
- **Blocked by:** 607 (matrix must exist first so this can be validated
  against all four PHP versions, not just the previous single-version CI)
- **External:** none

## Notes / decisions log

- 2026-07-21 — Filed as the explicit "out of scope" follow-up from ticket
  607, which pins PHPUnit 9.6 / PHPStan 1.12 and only matrixes PHP
  interpreter versions, not toolchain major versions.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
