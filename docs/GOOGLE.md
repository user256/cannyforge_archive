# Google Top-Content Sourcing (Search Console + GA4)

When Blog/Top mode has no curated URLs, the archive falls back to a best-effort
"top content" set so the promoted surface is never blank. Two of the fallback
tiers can be backed by real Google data:

1. **Search Console** — the primary Google signal (ticket 405).
2. **GA4** — an optional second Google signal (ticket 406), used only when
   Search Console returns nothing.

Both read from a locally cached list of post IDs that you refresh on demand from
the settings page. **Page renders never call Google** — the live archive only
reads the cache, so a slow or failing Google API never affects front-end latency.

## Fallback precedence

The Blog empty-list fallback chooses, in strict order:

1. **Google** — the cached Google IDs, where the Google source itself is the
   composite `Search Console → GA4` (first available signal with data wins).
2. **Most-commented** — only when at least one post has comments.
3. **Jetpack Stats** — when the Jetpack module is present.
4. **Newest** — the final floor.

GA4 is purely additive: it can only contribute when Search Console is
unconfigured or its cache is empty. Adding GA4 never weakens the Search
Console-first path.

## One-time Google Cloud setup

Both signals share a single OAuth client and connection.

1. In the [Google Cloud Console](https://console.cloud.google.com/), create (or
   reuse) a project.
2. **Enable the APIs** you intend to use:
   - *Google Search Console API* — for the Search Console signal.
   - *Google Analytics Data API* — for the GA4 signal.
3. Configure the **OAuth consent screen** (internal or external as appropriate)
   and add your admin account as a test user if the app is unpublished.
4. Create an **OAuth 2.0 Client ID** of type *Web application*.
5. Add the plugin's callback as an **Authorized redirect URI**. It is shown on
   the settings page and has the form:
   `https://your-site.example/wp-admin/admin-post.php?action=cannyforge_archive_google_callback`
6. Copy the **Client ID** and **Client secret** into the plugin's Google panel
   and save. The secret is encrypted at rest and never rendered back into the
   form.

The connect flow requests these read-only scopes:

- `https://www.googleapis.com/auth/webmasters.readonly` (Search Console)
- `https://www.googleapis.com/auth/analytics.readonly` (GA4)

## Search Console configuration

- **Search Console Site URL** — the property identifier, e.g.
  `sc-domain:example.com` (domain property) or `https://example.com/` (URL-prefix
  property). It must match a property your connected account can read.
- Click **Refresh Search Console** to fetch the top pages for the configured
  report window, map them to local published posts, and cache the result.

## GA4 configuration

GA4 has a little more setup than Search Console (hence its separate, optional
status):

- **GA4 Property ID** — the numeric property identifier (e.g. `123456789`),
  found under *Admin → Property → Property details* in the GA4 UI. Paste the
  digits only; a `properties/123456789` form is also accepted and normalised.
  Leave this blank to use Search Console only.
- The connected Google account must have at least *Viewer* access to that GA4
  property.
- Click **Refresh GA4** to run a `runReport` query for `screenPageViews` by
  `pagePath` over the report window, map the page paths to local published
  posts, and cache the result.

GA4 reports page **paths** (e.g. `/2024/my-post/`) rather than absolute URLs;
the plugin resolves them with WordPress's `url_to_postid()`, which accepts both
paths and full URLs.

## Report window & caching

- **Report window (days)** applies to both signals (1–365, default 30).
- Each signal has its own cache, stored outside the main archive settings
  option. Saving settings clears both caches; disconnecting Google clears both
  caches and the stored tokens.
- Misconfigured or empty Google data degrades cleanly: an unavailable or empty
  Google source simply falls through to the next fallback tier.
