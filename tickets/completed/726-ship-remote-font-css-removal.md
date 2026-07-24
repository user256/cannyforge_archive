# Ticket 726: Ship the remote Google Fonts removal and keep the packaging guard

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Complete
**Owner:** unassigned
**Estimate:** S
**Priority:** P0 — WordPress.org remote-asset rejection

---

## Context

`HEAD` still ships `assets/css/archive.css` with:

```css
@import url('https://fonts.googleapis.com/css2?family=Instrument+Serif:…&family=Inter:…');
```

WordPress.org Plugin Review treats hot-linked CSS/fonts as undeclared third-party
requests (Guideline 6 / common “remote assets” issues). The working tree already
removes the `@import` and the Inter/Instrument font-family overrides, and adds
`DistributablePackageTest::test_frontend_css_has_no_remote_imports`, but that fix
is not yet on the branch that would be built for submission.

## Goal

The distributable ZIP’s front-end stylesheet makes no remote `@import` / CDN font
requests, and a packaging test prevents regression.

## Acceptance criteria

- [x] `assets/css/archive.css` in the commit that builds the release ZIP has no
      `http(s)` `@import` and no Google Fonts dependency.
- [x] `tests/Packaging/DistributablePackageTest.php` asserts the built ZIP’s
      `archive.css` does not match a remote `@import` pattern.
- [x] Rebuilding via `composer dist` / `scripts/install-plugin.sh` and running the
      packaging test passes.
- [x] No other shipped CSS/JS hot-links remote static assets.

## Out of scope

- Redesigning archive typography beyond removing the remote fonts.
- Bundling Inter / Instrument Serif locally.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** WordPress.org remote-asset guidance

## Notes / decisions log

- 2026-07-24 — Working-tree CSS removal + packaging guard verified; full unit suite green.

- 2026-07-24 — Filed from [plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md).
  Working tree already contains the CSS removal and packaging assertion; this
  ticket exists to make sure that fix is committed and verified in the shippable
  artifact before upload.

---

## Definition of done

1. Acceptance criteria checked.
2. Fix is on the release branch / commit used for the wp.org ZIP.
3. Overview checkbox marked done.
4. Follow-ups filed, not absorbed.
