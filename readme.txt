=== CannyForge Archive Generator ===
Contributors: user256
Tags: sitemap, archive, pagination, seo, crawl-budget
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

HTML sitemap + JS archive, and a crawl-budget-friendly replacement for default taxonomy pagination.

== Description ==

CannyForge Archive Generator gives news and blog sites a combined HTML sitemap and
JavaScript-powered archive, and replaces WordPress's default taxonomy
pagination with a shorter sequence that links out to that archive.

Default pagination wastes crawl budget and leaks PageRank into deep, low-value
paginated pages. CannyForge Archive Generator shortens the visible pagination run and
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
3. Open *CannyForge Archive Generator* in the admin menu and configure the archive.
4. Visit `/archive/` (or your configured slug) to view the generated archive.

== External services ==

This plugin can connect to Google services to fetch top-content signals for the
archive. These integrations are optional and are only used when the site owner
configures Google credentials and explicitly connects an account.

= Google OAuth / Search Console / Google Analytics 4 =

What the service is used for:

* Google OAuth authenticates the site owner's Google account and refreshes API access tokens.
* Google Search Console can supply top page URLs for the configured property.
* Google Analytics 4 can supply top page paths as an optional fallback signal.

What data is sent:

* The configured OAuth client ID and client secret are used to exchange and refresh access tokens with Google.
* The configured Search Console property identifier is sent when requesting top-page rows.
* The configured GA4 property ID is sent when requesting report rows.
* Google access tokens are sent with those API requests.

What access is requested:

* The connect flow always requests the Search Console read-only scope (`https://www.googleapis.com/auth/webmasters.readonly`).
* The Analytics (GA4) read-only scope (`https://www.googleapis.com/auth/analytics.readonly`) is requested only when a GA4 property ID is configured, so a Search Console-only setup never asks for Analytics access.

When it happens:

* Only when the site owner clicks Connect or manually refreshes the Google-backed cache.
* Never during wp.org installation or by default on an unconfigured site.

How credentials are stored and removed:

* The client secret, refresh token, and cached access token are encrypted at rest.
* Clicking Disconnect makes a best-effort call to Google's token revocation endpoint before clearing the locally stored tokens and caches, so the connection is invalidated on Google's side as well as locally. If Google's revocation endpoint cannot be reached, the local credentials are still cleared and the admin is told the remote grant may need to be revoked manually.

Service policies:

* Terms of Service: https://policies.google.com/terms
* Privacy Policy: https://policies.google.com/privacy

== Frequently Asked Questions ==

= Does the archive work without JavaScript? =

Yes. The archive list is server-rendered and crawlable; the JavaScript only
adds client-side search and filtering on top.

= Does it replace my theme's pagination automatically? =

On targeted archive types it filters the theme's pagination output. Themes that
render pagination in a non-standard way can use the `[cannyforge_pagination]`
shortcode or the template tag instead.

= If I deactivate the plugin (or turn off pagination targeting), do I get my normal pagination back? =

Yes, completely and immediately. The shortened pagination is applied by
filtering WordPress's own pagination output at render time (`navigation_markup_template`);
nothing is written to your permalinks, post content, or database schema.
Deactivating the plugin, or unchecking an archive type under Pagination
Targeting in settings, restores the theme's default numbered pagination on
the very next page load — there is no residue to clean up.

= Exactly what data is sent to Google, and when? =

Nothing is sent to Google unless you explicitly configure Google credentials
and click Connect (or manually refresh a Google-backed cache) — never on
install and never by default. Once connected, only these get sent: your
configured OAuth Client ID/Secret (to exchange and refresh access tokens),
your Search Console property identifier and/or GA4 property ID (when
requesting top-page rows), and the resulting Google access token with those
API requests. See the "External services" section above for the full
disclosure, including exactly which OAuth scopes are requested and how
disconnecting revokes them.

= How does the archive cache behave, and when does it refresh? =

The rendered archive HTML is cached per mode (Blog / News / Hybrid) using the
WordPress Transients API for up to 24 hours. That cache is cleared
immediately — not after the TTL — whenever you save the plugin's settings, or
whenever a post/page is saved or deleted, or a term or author changes, so the
archive reflects your latest configuration and content right away in the
cases that matter. A change written directly to the `cannyforge_archive_settings`
option outside the settings UI (bypassing that save action) only takes effect
on the next invalidating event or once the 24-hour TTL expires.

== Screenshots ==

1. The settings page: archive mode, content selection, theme, pagination, and Google top-content setup in one screen.
2. The generated archive / HTML sitemap page, with the client-side search and category / tag / month / author filters.
3. The shortened pagination block ("1, 2, 3 … View Archive") replacing a theme's default deep pagination run on a targeted archive listing.

Note for reviewers: the numbered captions above are reserved, but the
screenshot image files themselves are not yet included in this submission —
see the plugin's ticket 610 decisions log for why (capturing them needs a
real rendered WordPress site, not available at generation time) and ticket
618 for the follow-up to capture and add them before this listing goes live.

== Changelog ==

= 0.1.1 =
* Sprint 2 hardening: archive endpoint lifecycle, content-selection
  normalisation, distributable build helper, front-end theming controls,
  historic-content seeding, fragment caching, extensibility hooks, and
  CSS moved to enqueued stylesheets.

= 0.1.0 =
* Initial release: archive page, News and Blog modes, client-side filters,
  pagination replacement, SEO controls, and content selection.
