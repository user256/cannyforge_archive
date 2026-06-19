=== Archive Generator ===
Tags: sitemap, archive, pagination, seo, crawl-budget
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

HTML sitemap + JS archive, and a crawl-budget-friendly replacement for default taxonomy pagination.

== Description ==

Archive Generator gives news and blog sites a combined HTML sitemap and
JavaScript-powered archive, and replaces WordPress's default taxonomy
pagination with a shorter sequence that links out to that archive.

Default pagination wastes crawl budget and leaks PageRank into deep, low-value
paginated pages. Archive Generator shortens the visible pagination run and
routes crawlers to a single rich archive page, helping sculpt PageRank toward
the content that matters — fresh articles on news sites, evergreen posts on
blogs.

Features:

* HTML-sitemap archive page at a crawlable, no-JavaScript URL.
* News mode: include content published within a configurable recent window.
* Blog mode: a curated URL list, entered manually or imported from CSV.
* Client-side search and category / tag / month / author filters.
* Shortened pagination with a "View Archive" link, targeted per archive type.
* SEO controls: archive title, meta description, robots directives, canonical.
* Content selection: include / exclude by term, drop noindex, pin URLs first.

== Installation ==

1. Upload the `cannyforge-archive` folder to `/wp-content/plugins/`.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Open *Archive Generator* in the admin menu and configure the archive.
4. Visit `/archive/` (or your configured slug) to view the generated archive.

== Frequently Asked Questions ==

= Does the archive work without JavaScript? =

Yes. The archive list is server-rendered and crawlable; the JavaScript only
adds client-side search and filtering on top.

= Does it replace my theme's pagination automatically? =

On targeted archive types it filters the theme's pagination output. Themes that
render pagination in a non-standard way can use the `[cannyforge_pagination]`
shortcode or the template tag instead.

== Changelog ==

= 0.1.1 =
* Sprint 2 hardening: archive endpoint lifecycle, content-selection
  normalisation, distributable build helper, front-end theming controls,
  historic-content seeding, fragment caching, extensibility hooks, and
  CSS moved to enqueued stylesheets.

= 0.1.0 =
* Initial release: archive page, News and Blog modes, client-side filters,
  pagination replacement, SEO controls, and content selection.
