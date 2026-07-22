# Ticket 706: Make the disposable integration rig rebuild-safe

**Sprint:** 7 — Modernisation (proposed)
**Status:** Complete
**Owner:** Codex
**Estimate:** XS

## Result

`composer test:integration` now stops an existing wp-env project before
rebuilding `dist/`, preventing Docker from retaining an empty nested plugin
mount. Composer's process timeout is 900 seconds so the deliberately slow
48-post fixture seed can complete on this wp-env/CLI combination.

## Verification

- [x] Clean `composer test:integration` run passed 11 tests and 97 assertions.
- [x] The disposable rig was stopped by the runner cleanup.
