# Plugin audit — 2026-07-21

## Outcome

**No-Go for release/submission from the current working tree.** The source has
strong layering and a broad unit/static-analysis toolchain, but the current UI
work fails most release gates and the generated ZIP contains a development
script. Two shipped lifecycle defects and several security/UX/interoperability
gaps need tickets before the Sprint 6 review gate.

## Evidence collected

| Check | Result |
|---|---|
| `composer qa` / PHPCS | Fail: 42 errors in `src/Admin/SettingsView.php` |
| `composer stan` | Fail: 8 errors |
| `composer rector` | Fail: 1 proposed change |
| `composer mess` | Fail: 6 class/method length violations |
| `composer test` | Fail: 1 failure, 200 tests / 523 assertions |
| `composer validate --strict --no-check-publish` | Fail: stale lock content hash |
| PHParkitect | Pass |
| Deptrac | Pass: 0 violations (15 uncovered dependencies) |
| `git diff --check` | Fail: trailing whitespace in CSS/PHP |
| `composer dist` | Builds, but ZIP includes `rebuild_ui.py` |
| PHP syntax over staged distribution | Pass |
| External policy/privacy URLs | Google Terms and Privacy returned 200 |

## New tickets

- [611 — Restore the release gate and package only runtime files](611-release-branch-stabilisation.md)
- [612 — Fix archive-tail redirects and Hybrid cache invalidation](612-archive-route-cache-correctness.md)
- [613 — Make the redesigned admin settings UI truthful, complete, and accessible](613-admin-settings-ui-integrity.md)
- [614 — Enforce least-privilege Google OAuth and revoke credentials on disconnect](614-google-oauth-least-privilege-lifecycle.md)
- [615 — Prevent duplicate SEO tags and define canonical ownership](615-seo-plugin-interoperability.md)

These complement rather than replace tickets 601–610. In particular, 601/602
create the missing test harnesses, 603 provides real WordPress coverage, 605
owns the cipher redesign, 606 owns uninstall cleanup, 608 owns scale/abuse
controls, 609 owns public archive accessibility, and 610 owns i18n/listing
assets.

## Positive findings

- Main runtime PHP files have direct-access guards and the distribution uses a
  small first-party autoloader instead of shipping development dependencies.
- Public search inputs are sanitized and bounded by `ContentQuery`; published
  posts are the only query target.
- Admin state-changing handlers consistently perform capability/nonce checks on
  their normal POST paths.
- Dynamic front-end values are generally escaped late, external static assets
  are not hot-linked, and the optional Google service is disclosed in
  `readme.txt` with Terms/Privacy links.
- Current WordPress `Tested up to: 7.0` matches the latest stable major release
  (7.0.2 at audit time), although ticket 607 still needs to prove compatibility.

## Reference baseline

- [WordPress detailed plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [WordPress common review issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- [WordPress Plugin Check guidance](https://developer.wordpress.org/plugins/developer-tools/helper-plugins/)
- [WordPress readme format](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [WordPress release archive](https://wordpress.org/download/releases/)
