# Ticket 203: Distributable build helper + ZIP output

**Sprint:** 2 — Hardening & Fit
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

Ticket 120 established what must ship in a distributable plugin and added
`.distignore`, but the repo still has no canonical build/install helper. That
leaves packaging knowledge implicit and makes it too easy to deploy the wrong
tree again. The next step is a repeatable repo-native build command that creates
the filtered plugin folder and a WordPress-installable ZIP.

## Goal

A single repo-native build step produces both the distributable plugin directory
and a ZIP archive from the same filtered file list.

## Acceptance criteria

- [x] A documented build/install helper exists in the repo and uses `.distignore` as the source of truth.
- [x] Running the helper creates a clean distributable directory named for the plugin slug and a ZIP archive ready for WordPress upload.
- [x] The helper excludes dev/docs/test artefacts and includes runtime files required by the plugin.
- [x] `README.md` documents the build command.

## Out of scope

- Publishing to the WordPress.org plugin directory.
- CI/CD automation beyond the local build helper itself.

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** a shell environment with `rsync` and `zip`.

## Notes / decisions log

- 2026-06-18 — Filed when no packaging helper was present in-repo even though `.distignore` already exists.
- 2026-06-18 — Implemented via `scripts/install-plugin.sh`, Composer scripts, and README usage notes; local build/install smoke passed against `/var/www/html`.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket — not silently absorbed.
