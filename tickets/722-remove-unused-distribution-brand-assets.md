# Ticket 722: Remove unused branding assets from the distributable ZIP

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Not started
**Owner:** unassigned
**Estimate:** S
**Priority:** P3 — package hygiene

---

## Context

The distributable contains four branding SVGs, but the admin UI references only
`assets/branding/cannyforge-font-light.svg`. Unused files are not a security
defect, but WordPress.org review guidance asks plugins not to ship unnecessary
files and folders.

## Goal

The release ZIP contains only branding assets that the plugin uses or explicitly
documents as runtime-facing assets.

## Acceptance criteria

- [ ] Each branding asset in the package has a runtime reference or is removed
      from source and the distribution allowlist.
- [ ] The admin header remains visually intact after rebuilding the ZIP.
- [ ] Package inspection confirms no unused branding SVG remains in `dist/`.
- [ ] `composer qa` and the package build/check pass.

## Out of scope

- Redesigning the CannyForge brand system.
- Adding WordPress.org directory assets, which have separate packaging rules.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit. The currently referenced
  light wordmark must be retained unless the UI reference changes with it.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any intentional non-runtime asset is documented rather than silently kept.
