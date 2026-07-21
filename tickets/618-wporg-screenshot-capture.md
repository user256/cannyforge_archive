# Ticket 618: Capture wp.org listing screenshots

**Sprint:** 6 — Trust & Scale
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Ticket 610 (i18n completeness + wp.org listing assets) prepared the wp.org
`/assets` deliverables it could produce without a live, safe-to-use WordPress
site: `.wordpress-org/banner-1544x500.png`, `banner-772x250.png`,
`icon-256x256.png`, and `icon-128x128.png` (rasterized from
`assets/branding/logo-full-dark.svg` and `cannyforge-icon-only.svg` via
ImageMagick's `convert`). It also added the `== Screenshots ==` section to
`readme.txt` with three reserved, numbered captions — but no actual
`screenshot-1.png` / `screenshot-2.png` / `screenshot-3.png` files, because
generating them honestly requires a running WordPress instance with the
plugin installed and in a specific, deliberately-staged state.

During ticket 610 it was confirmed a live WordPress instance *does* exist on
the build sandbox (`/var/www/html`, nginx + php8.3-fpm + mysql, reachable at
`http://127.0.0.1/`, with `cannyforge-archive` already listed as an active
plugin via `wp plugin list --allow-root`) — this is the same site
`composer install:local` / `composer seed:historic` target per the README.
However, at the time ticket 610 ran, that instance was serving unrelated live
front-end content (an affiliate/review site, "Casino Playground", with real
gambling-review posts and its own active theme) alongside several *other*
inactive plugins (`agency-lead-capture`, `roofing-lead-capture`,
`medspa-lead-capture`, etc.) — i.e. it's a shared, multi-project dev sandbox,
not a clean instance provisioned solely for this ticket. Screenshotting
through that theme/content would misrepresent the plugin (readers would see
gambling-site branding wrapped around CannyForge's UI) and mutating a shared
instance's active theme/content risked interfering with whatever concurrent
work might be relying on its current state. Ticket 610 deliberately did not
touch it.

Chromium (`chromium-browser`, headless) and Firefox are both installed and
functional on the sandbox (`chromium-browser --headless --screenshot=...
--window-size=W,H URL` was smoke-tested successfully during ticket 610), so
headless screenshot capture itself is not a blocker — only having a clean,
exclusively-owned WordPress instance to point it at is.

## Goal

Three real, submission-ready PNG screenshots exist at
`.wordpress-org/screenshot-1.png` / `screenshot-2.png` / `screenshot-3.png`,
captured from an actual rendered WordPress site running this plugin, matching
the captions already reserved in `readme.txt`'s `== Screenshots ==` section.

## Acceptance criteria

- [ ] A WordPress instance is available that is either (a) exclusively owned
      for this capture session (a fresh `composer install:local` target with
      a default theme, e.g. Twenty Twenty-Four, and no unrelated content), or
      (b) ticket 603's real-WordPress integration rig, if it lands first and
      can render pages headlessly.
- [ ] `composer seed:historic` (or equivalent) has run so the archive page
      and a targeted taxonomy archive have enough dated content to show real
      pagination and filters, not an empty state.
- [ ] `screenshot-1.png`: the plugin's settings page
      (`wp-admin/admin.php?page=cannyforge-archive`), showing the mode
      selection, content selection, theme, and pagination sections.
- [ ] `screenshot-2.png`: the generated archive / HTML sitemap page
      (`/archive/` or the configured slug) with the search box and
      category/tag/month/author filter controls visible.
- [ ] `screenshot-3.png`: a targeted category/tag archive listing showing the
      shortened pagination block ("1, 2, 3 … View Archive") in place of the
      theme's default deep pagination run.
- [ ] All three are placed under `.wordpress-org/` (matches the wp.org SVN
      `/assets` convention already established by ticket 610; excluded from
      the plugin ZIP via `.distignore`).
- [ ] `readme.txt`'s `== Screenshots ==` section has its "reviewer note" about
      missing image files removed once the three files actually exist.

## Out of scope

- Any change to the plugin's own settings/archive/pagination behaviour —
  this ticket only captures screenshots of existing, already-shipped
  behaviour.
- Redesigning or re-theming the capture site beyond switching to a default
  WordPress theme for a clean, representative screenshot background.

## Dependencies

- **Blocks:** the wp.org listing going live (readme.txt currently documents
  reserved-but-empty screenshot slots).
- **Blocked by:** none strictly, but may be much easier once ticket 603 (real
  WordPress integration rig) lands, since that gives a purpose-built,
  exclusively-owned instance instead of needing to carve out safe time on the
  shared sandbox site.
- **External:** a WordPress instance safe to mutate (own the whole box, or
  ticket 603's rig).

## Notes / decisions log

- 2026-07-21 — Filed during ticket 610. Confirmed headless screenshot
  tooling (chromium-browser) works on the sandbox; the blocker is a clean,
  exclusively-owned WordPress instance, not tooling availability.

---

## Definition of done

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is flipped to `- [x]`.
4. Follow-up work discovered during implementation is filed as a new ticket.
