# Ticket 501: WordPress.org audit remediation

**Sprint:** 5 — wp.org submission compliance
**Status:** Done
**Owner:** user256
**Estimate:** M

---

## Context

The pre-submission audit against `WordPressAudit.md` found several issues that
would likely block a WordPress.org review even though the local QA suite was
green. The shipped ZIP included the full development Composer tree, every
first-party PHP file under `src/` was missing a direct-access guard, the public
plugin name was still generic, and the readme lacked required external-service
disclosures for the optional Google integrations.

## Goal

The distributed plugin is review-ready for the audited issues: runtime-only,
guarded against direct file access, distinctly branded, and documented for its
optional external Google services.

## Acceptance criteria

- [x] The runtime plugin no longer depends on Composer `vendor/autoload.php`, and the distributable build excludes `vendor/` and other development-only files.
- [x] Every shipped first-party PHP file under `src/` bails when `ABSPATH` is undefined.
- [x] The public plugin name is updated from the generic `Archive Generator` to `CannyForge Archive Generator` in the plugin header, readme, and admin UI.
- [x] `readme.txt` includes a `Contributors:` header and an `External services` section documenting the optional Google integrations with Terms and Privacy links.
- [x] `vendor/bin/phpcs --report=full --standard=phpcs.xml.dist cannyforge-archive.php src tests` passes.
- [x] `vendor/bin/phpunit` passes.

## Out of scope

- Changing the plugin slug or text domain.
- Solving any future trademark or wp.org username reservation issue outside the local repo.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** WordPress.org plugin review guidelines; Google service policies linked from the readme

## Approach

Replace the runtime Composer dependency with a tiny plugin-local PSR-4
autoloader, tighten the dist filter, sweep `src/` with a consistent `ABSPATH`
guard, and update the public-facing metadata/readme copy to match the branded
shipping plugin.

## Notes / decisions log

- 2026-06-25 — Replaced runtime Composer autoload with a small plugin-local autoloader so the shipped ZIP can omit `vendor/`.
- 2026-06-25 — Added `ABSPATH` guards to every first-party PHP file in `src/`; tests define `ABSPATH` in bootstrap so the suite remains green.
- 2026-06-25 — Used `user256` as the local default `Contributors:` value; confirm the real wp.org username before submission.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
