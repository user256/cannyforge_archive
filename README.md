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

- **Contracts** — interfaces the engine and composition root share.
- **Core** — the archive/sitemap engine and pagination logic.
- **Bootstrap** — the composition root; the only layer that wires to WordPress.

Gated out of the box:

- **PHPStan** (Level 9) — strong static typing.
- **PHPCS** — WordPress Coding Standards enforcement.
- **PHParkitect** — architectural boundary validation.
- **Deptrac** — cross-layer dependency enforcement.
- **PHPMD** — hard-failing cyclomatic complexity and god-class limits.
- **Rector** — dead code detection.
- **PHPUnit** — unit testing.

## Development Workflow

- Run `composer install` to pull the dev toolchain.
- Run `composer qa` to execute all tests and static analysis (the merge gate).
- Run `composer cs:fix` to automatically resolve formatting issues.
- Document planned work as markdown tickets in [`tickets/`](tickets/).

## License

GPL-2.0-or-later.
