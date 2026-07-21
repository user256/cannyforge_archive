# CannyForge Archive

A WordPress plugin that gives news and blog sites a combined **HTML sitemap +
JS-powered archive**, and replaces the default taxonomy pagination with a
shorter, crawl-budget-friendly sequence that links out to the archive.

## Why

Default WordPress pagination wastes crawl budget and leaks PageRank into deep,
low-value paginated taxonomy pages. CannyForge Archive shortens the visible
pagination run and routes crawlers to a single rich archive/HTML-sitemap page,
helping sculpt PageRank toward the content that matters (fresh articles on news
sites, evergreen posts on blogs).

See [`docs/PLAN.md`](docs/PLAN.md) for the full product brief and back-end
mock-ups.

## What it does

- **Archive / HTML sitemap** — a single browsable page (similar to large news
  sites' HTML sitemaps) with optional client-side search and category / tag /
  month+year / author filters.
- **Pagination replacement** — a limited pagination sequence with a
  "View Archive" link, configurable depth before the archive takes over.
- **Blog or News mode** — generate either a Blog sitemap (curated top URLs) or
  a News sitemap (everything published within a configurable recent window).

## Architecture & Quality Gates

Layered with enforced seams (see [`deptrac.yaml`](deptrac.yaml)):

- **Contracts** — the shared seam: persistence interfaces and the framework-free
  settings value objects. Depends on nothing internal.
- **Core** — the engine: the WordPress-backed settings repository and (upcoming)
  the archive/sitemap generator and pagination logic. Depends only on Contracts.
- **Admin** — the WordPress admin surface (settings page, form rendering and
  parsing). Depends on Contracts + Core.
- **Frontend** — the WordPress front-end surface (the archive page rewrite
  endpoint and its rendering). Depends on Contracts + Core.
- **Bootstrap** — the composition root; the only layer that wires everything
  together against WordPress hooks.

Gated out of the box:

- **PHPStan** (Level 9) — strong static typing.
- **PHPCS** — WordPress Coding Standards enforcement.
- **PHParkitect** — architectural boundary validation.
- **Deptrac** — cross-layer dependency enforcement.
- **PHPMD** — hard-failing cyclomatic complexity and god-class limits.
- **Rector** — dead code detection.
- **PHPUnit** — unit testing.

## Development Workflow

- Run `composer install` to pull the dev toolchain. Composer resolves the
  development lock against PHP 8.1, the plugin's supported minimum, so a
  newer local PHP version cannot silently raise the compatibility floor.
- Run `composer dist` to build `dist/cannyforge-archive/` plus a WordPress-uploadable ZIP.
- Run `composer install:local` to build, zip, and install into the local WordPress site at `/var/www/html`.
- Run `composer seed:historic` to generate older posts on the local WordPress site for archive/pagination smoke testing.
- Run `bash scripts/install-plugin.sh /path/to/wp-content/plugins` if you want to target a different plugins directory directly.
- Run `bash scripts/seed-historic-content.sh --site-path /var/www/html --count 120` to seed a WordPress test site with dated archive posts for smoke testing.
- Run `composer qa` to execute all tests and static analysis (the merge gate).
- Run `composer cs:fix` to automatically resolve formatting issues.
- Document planned work as markdown tickets in [`tickets/`](tickets/).
- Keep git commit messages to a single summary line.

## Release Procedure

1. Bump the version in `cannyforge-archive.php` (both the `Version:` header
   and the `CANNYFORGE_ARCHIVE_VERSION` constant) and in `readme.txt`
   (`Stable tag:`), and add a changelog entry to `readme.txt`.
2. Run `composer i18n` to regenerate `languages/cannyforge-archive.pot` from
   the current source strings (WP-CLI's `wp i18n make-pot`, requires WP-CLI
   on `PATH`). Commit the regenerated `.pot` — it's translators' source of
   truth and ships inside the plugin build. Translating the strings
   themselves (producing `.po`/`.mo` files) is out of scope; this step only
   keeps the template current.
3. Run `composer qa` and confirm it's green, including the `WordPress.WP.I18n`
   PHPCS sniff (gates the `cannyforge-archive` text domain on every
   translation call).
4. Run `composer dist` to build `dist/cannyforge-archive/` plus the
   distributable ZIP.
5. Update the wp.org listing assets under `.wordpress-org/` (banner, icon,
   screenshots) if the visuals changed; they are not part of the plugin ZIP
   (see `.distignore`) and are uploaded separately to the plugin's SVN
   `assets/` directory.

## Archive Smoke Data

The historic-content seeder creates published posts spread across multiple years,
categories, tags, and authors so the archive has enough depth to exercise:

- archive-page rendering on older content
- search and filter behaviour in the browser
- pagination replacement on deep taxonomy archives

The archive filters are client-side JavaScript, not AJAX, so the relevant smoke
check is browser behaviour on the rendered archive page rather than an API call.

Recommended smoke checklist after `composer seed:historic`:

- Open `/archive/` and confirm older seeded posts render across multiple historical months/years.
- Type in the archive search box and confirm the visible list updates without a page reload.
- Change the category, tag, month, and author filters and confirm each narrows the rendered list client-side.
- Visit a targeted category/tag/date/author archive with pagination and confirm the shortened pagination block still links to `/archive/`.
- Confirm the archive stylesheet and inline theme variables are present on the archive page and targeted archive listings.
- Visit `/archive/unwanted-tail/` (any non-empty tail after the endpoint) and confirm a 301 to the configured `archive_url` when WordPress accepts it, otherwise the local `/archive/` endpoint, never a blank page (tickets 612/617).
- Switch the mode to Hybrid, save settings, publish a post, and confirm `/archive/` reflects the change immediately rather than after the 24-hour cache TTL (ticket 612 — `ArchiveCache::clear()` now clears every `Mode` case, including Hybrid).

These two are flagged for automation in ticket 603 (real-WordPress integration rig); until that lands they remain manual checks.

### Accessibility smoke checklist (ticket 609)

Automated axe-core-in-CI coverage for the archive page is blocked on ticket
603 (real-WordPress integration rig); until that lands, verify these by hand
after `composer seed:historic`:

- Screen-reader walkthrough (VoiceOver/NVDA): search the archive box and
  confirm the result count is announced (e.g. "3 results for 'query'"), type
  a query with no matches and confirm the "no results" message is announced
  too, then Tab through the search box, every filter select, the Reset
  button, and the Prev/Next pagination controls and confirm each has an
  audible, distinct label.
- Keyboard-only walkthrough (no mouse): confirm every filter control, the
  Reset button, and Prev/Next pagination buttons are reachable via Tab and
  operable via Enter/Space, each with a visible focus outline.
- Open the admin "Edit Colours" dialog, set the Text Color to something close
  to the Surface Color, and confirm a contrast warning appears near the
  colour pickers; fix the colour back and confirm the warning clears.
- In your OS accessibility settings, enable "reduce motion" and reload the
  archive page; confirm the entrance fade-in and hover-lift animations on
  archive cards/pagination no longer move.

## License

GPL-2.0-or-later.
