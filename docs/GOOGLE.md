# Google Top-Content Sourcing (Search Console, Analytics, or both)

When Blog/Top mode has no curated URLs, the archive falls back to a best-effort
"top content" set so the promoted surface is never blank. The wizard offers
three Google signal paths:

1. **Search Console** — use Search Console top content only.
2. **Analytics** — use GA4 top pages as the primary Google signal.
3. **Search Console + GA4 fallback** — use Search Console first, then GA4 when
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

GA4 is additive only in the combined path. In Analytics-only mode it is the
primary Google signal.

## One-time Google Cloud setup

All paths share a single OAuth client and connection.

1. In the [Google Cloud Console](https://console.cloud.google.com/), create (or
   reuse) a project.
2. **Enable the APIs** you intend to use:
   - *Google Search Console API* — for the Search Console signal.
   - *Google Analytics Admin API* — to list GA4 accounts and properties in the property picker.
   - *Google Analytics Data API* — for the GA4 signal's top-page reports.
3. Configure the **OAuth consent screen** (internal or external as appropriate)
   and add your admin account as a test user if the app is unpublished.
4. Create an **OAuth 2.0 Client ID** of type *Web application*.
5. Add the plugin's callback as an **Authorized redirect URI**. It is shown on
   the settings page and has the form:
   `https://your-site.example/wp-admin/admin-post.php?action=cannyforge_archive_google_callback`
6. Copy the **Client ID** and **Client secret** into the plugin's Google panel
   and save. The secret is encrypted at rest and never rendered back into the
   form.

The connect flow requests scopes least-privilege, based on what's actually
configured (ticket 614):

- `https://www.googleapis.com/auth/webmasters.readonly` (Search Console) —
  requested for Search Console-only and combined wizard paths.
- `https://www.googleapis.com/auth/analytics.readonly` (GA4) — requested when
  the Analytics-only or combined wizard path is selected, or when a GA4
  Property ID is already configured. A Search Console-only setup never asks
  the admin to grant Analytics access it will not use.

The settings page or wizard shows the exact scopes that will be requested,
next to the Connect button, before the admin is redirected to Google — so
what's granted always matches the selected/configured integration, not what
the plugin could theoretically use.

## Search Console configuration

- Connect Google first. The plugin calls the Search Console sites endpoint and
  lists the properties available to that Google account in the **Search Console
  property** dropdown. Select one and save the settings; no property identifier
  needs to be copied manually.
- Use **Load Search Console properties** to refresh the dropdown after changing
  accounts or permissions. If a previously saved property is no longer returned,
  it remains visible as a saved value until you choose another one.
- Click **Refresh Search Console** to fetch the top pages for the configured
  report window, map them to local published posts, and cache the result.

## GA4 configuration

GA4 has a separate property picker and can be used on its own or as the
combined path's fallback:

- When the wizard's Analytics-only or Search Console + GA4 signal is selected, the connect flow
  uses `https://analyticsadmin.googleapis.com/v1beta/accountSummaries` after OAuth to
  list the connected account's GA4 properties. It sends the Google access
  token and receives account/property names and IDs for the picker; it does
  not send report data in this request.
- **Load GA4 properties** makes the same `accountSummaries` request on demand
  with the stored Google access token. This requires the Analytics read-only
  scope and the Analytics Admin API to be enabled in Google Cloud.

- **GA4 Property ID** — the numeric property identifier (e.g. `123456789`),
  found under *Admin → Property → Property details* in the GA4 UI. Paste the
  digits only; a `properties/123456789` form is also accepted and normalised.
  Leave this blank when the selected path does not use Analytics.
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

## Token storage and disconnect (ticket 614)

- The client secret, the OAuth refresh token, and the cached access token are
  all encrypted at rest via the same `SecretCipher`. Values are authenticated
  encryption (AEAD) — `sodium_crypto_secretbox` when the PHP `sodium`
  extension is available (the default on any supported PHP 8.1+ build),
  falling back to AES-256-GCM via OpenSSL — keyed from the WordPress auth
  salt (ticket 605). Unlike the original unauthenticated AES-256-CBC scheme,
  a tampered ciphertext or a key that no longer matches is detected and
  rejected instead of silently producing garbage.
- A site upgrading from an older version that stored the access token in
  plaintext, or under the original unauthenticated `enc:` scheme, keeps
  working without a manual migration step: both a legacy plaintext value and
  a legacy `enc:` value still decrypt correctly, and the first successful
  read of either opportunistically re-encrypts and re-stores it under the
  new AEAD scheme. If no AEAD backend (`sodium` or OpenSSL AES-256-GCM) is
  available at all, the plugin refuses to store new secrets in plaintext —
  it shows a persistent admin notice on the settings page instead, and
  existing legacy values are left as-is (still readable) until a backend
  becomes available.
- **Key-rotation caveat:** the encryption key is derived from
  `wp_salt('auth')`. Rotating the WordPress security salts/keys (a normal,
  recommended incident-response step, and something some hosts/security
  plugins do automatically) changes that derived key, so every previously
  stored Google secret becomes undecryptable — the ciphertext fails
  authentication rather than silently returning corrupted data. The Google
  settings panel surfaces this as a distinct **"Needs re-authorising"**
  connection status (instead of a blank field or a misleading
  "Disconnected"), with a note that the site's security keys may have
  changed. **Recovery path:** re-enter the Google Client Secret (from the
  Google Cloud Console — it isn't recoverable from the old ciphertext) and
  click **Connect Google** again; nothing else needs to change, and no data
  beyond the Google connection itself is affected by a salt rotation.
- Clicking **Disconnect** makes a best-effort call to Google's token
  revocation endpoint (`https://oauth2.googleapis.com/revoke`) before clearing
  local state, so the grant is invalidated on Google's side, not just locally.
  Disconnecting is idempotent: calling it again (or with no stored tokens)
  does not error, and local cleanup always happens even when the remote call
  fails, times out, or Google is unreachable — the admin is told when that
  happens so they can revoke access manually from their Google Account if
  needed.
- The OAuth callback validates and consumes the CSRF state transient before
  anything else can change the connection status, including the provider
  `error` path — a callback hit directly with a forged `error` parameter and
  no valid state cannot flip the connection into an error state.
- Uninstalling the plugin (ticket 606) reuses this same revocation call rather
  than duplicating the network/token logic, so a stale grant doesn't linger in
  the admin's Google account after full removal either. Uninstalling also
  removes the fixed archive caches and all user-scoped Google property-list
  and OAuth-state transients. Search-result and rate-limit transients use
  bounded TTLs and are allowed to expire naturally rather than being swept
  during uninstall.
