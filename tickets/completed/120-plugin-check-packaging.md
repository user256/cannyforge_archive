# Ticket 120: Plugin Check failures — packaging & readme readiness

**Sprint:** 1 — Settings & MVP (hardening)
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Running the **Plugin Check** tool against the deployed plugin on the live
instance reported **11 errors + 4 warnings**. Two root causes:

1. **The deploy shipped the entire dev repo** into `wp-content/plugins/`, so
   dev-only files were flagged: `.env`, `.gitignore`, `.phpunit.result.cache`,
   `.deptrac.cache`, `tickets/completed/.gitkeep` (hidden files); `phpunit.xml.dist`,
   `phpstan.neon.dist`, `phpcs.xml.dist` (application/config files); `.github/`
   (workflow dir); `CannyForge-Archive-Overview.md` + the tickets tree (unexpected
   markdown); `phpinsights.php` (perf note on `exclude`). None of these belong in
   a distributable plugin.
2. **`README.md` is a GitHub readme, not a WordPress `readme.txt`** — missing the
   `Tested up to`, `License`, and `Stable Tag` headers, and the short description
   exceeds 150 chars.

## Goal

A clean, Plugin-Check-passing distributable: only runtime files ship, and a
WordPress-format `readme.txt` with the required headers.

## Acceptance criteria

- [x] A `.distignore` keeps dev files out of the shipped plugin: `.env`, `.git*`,
      caches, `*.dist`, `.github/`, `tickets/`, `docs/`, `tests/`, `node_modules/`,
      `composer.*`, the linter configs (`phpcs.xml*`, `phpstan*`, `rector.php`,
      `phparkitect.php`, `phpmd.xml`, `phpinsights.php`, `infection.json5`,
      `deptrac.yaml`), `process_tickets.py`, and stray root markdown.
- [x] A WordPress `readme.txt` with valid headers: `Stable tag` 0.1.0 (matches the
      main file's `Version`), `Tested up to: 6.7`, `License: GPLv2 or later` +
      `License URI`; short description 99 chars (≤ 150).
- [x] The deploy step uses `--exclude-from=.distignore`; the live install now
      ships only `assets/`, `cannyforge-archive.php`, `readme.txt`, `src/`,
      `vendor/`.
- [x] Verified directly on the live deploy: all 11 previously-flagged files are
      absent and the readme headers are present (the Plugin Check CLI itself
      couldn't run — it needs write access to `wp-content/object-cache.php`, owned
      by www-data — so verification was done by inspecting the deployed tree).
      The `phpinsights.php` perf note is moot now that file is excluded.

## Out of scope

- The two functional defects found in the live smoke (archive document/SEO and
  the pagination hook) — those are tracked separately.

## Notes / decisions log

- 2026-06-18 — Found via Plugin Check on the live instance after the first
  (whole-repo) deploy. The vendor autoloader must still ship; only dev/config/docs
  are excluded.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is updated.
4. Any follow-up work discovered during implementation is filed as a new ticket.
