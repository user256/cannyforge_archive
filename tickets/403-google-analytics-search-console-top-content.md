# Ticket 403: Google GA4 / Search Console "top content" sourcing (exploratory)

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Not started (exploratory — needs sign-off before build)
**Owner:** unassigned
**Estimate:** L (XL if the OAuth connect flow must be built from scratch)

---

## Context

Ticket 402 gives Blog/Top mode a *proxy* for popularity (comment_count → Jetpack
→ newest) because **WordPress core has no real traffic data**. The genuine signal
lives in Google: **GA4** (pageviews per URL) and **Search Console** (clicks /
impressions per URL). Sourcing "top content" from there would make the promoted
set reflect what actually performs, not a proxy.

The sibling plugin **`cannyforge-lead-capture`** already integrates with Google
over OAuth2 and is built on the same architecture (PSR-4 `CannyForge\`, PHP 8.1,
injected `wp_remote_*` transports, no `google/apiclient`, PHPStan L9, deptrac
layers). A read-only exploration (2026-06-23) found its token-handling
infrastructure is highly liftable, but the initial OAuth *connect* flow is
missing there and would need porting from the upstream `solar-form` reference.

This ticket scopes that integration. It is **exploratory**: the acceptance
criteria below are the target, but the design/spike sub-task gates the build.

## Goal

In Blog/Top mode, the archive can source its promoted "top content" set from a
connected Google account's GA4 pageviews and/or Search Console clicks, mapped to
local posts, with the ticket-402 proxy as the fallback when Google is not
connected or returns nothing.

## What is liftable from `cannyforge-lead-capture` (from 2026-06-23 exploration)

High reuse (copy/extract, change scopes only):

- `src/Premium/Google/Google_Oauth_Client.php` — token endpoint
  `https://oauth2.googleapis.com/token`, `grant_type=refresh_token` refresh,
  injected HTTP transport. **Scope-agnostic.**
- `src/Premium/Google/Token_Store.php` — encrypted refresh-token storage +
  access-token caching (90s TTL buffer), option-key namespaced via a
  `Configuration` prefix.
- `src/Core/Security/Secret_Cipher.php` — AES-256-CBC at-rest encryption keyed off
  the WP auth salt.
- `src/Premium/Sheets/Sheets_Client.php` — the reusable
  `wp_remote_request(method, url, Bearer token, body) → {code, data}` HTTP wrapper
  pattern; adapt to GET + query params for the GA/GSC report endpoints.

Missing / must build:

- **OAuth authorization-code connect flow** (the "Connect Google" button →
  consent → `admin-post.php` callback → code-for-tokens exchange → store refresh
  token, with `state` CSRF transient). Lead-capture only *refreshes* an existing
  token; the connect flow lives in `solar-form`
  (`class-solar-lead-capture-mail-oauth.php`, ~lines 581–609) as a template.
- **GA4 Data API client** — `POST
  https://analyticsdata.googleapis.com/v1/properties/{propertyId}:runReport`,
  scope `https://www.googleapis.com/auth/analytics.readonly`.
- **Search Console client** — `POST
  https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query`,
  scope `https://www.googleapis.com/auth/webmasters.readonly`.
- **URL → post_id mapping** of the rows Google returns (reuse `url_to_postid()`,
  already used by `BlogEntryProvider::resolve()`).

## Acceptance criteria (target — confirm in design spike first)

- [ ] Design spike completed and signed off: shared-library vs vendored-copy
      decision for the Google OAuth/token classes; GA4 vs GSC (or both) as the
      first source; where credentials live in the archive admin.
- [ ] Admin can register an OAuth client (ID/secret, stored encrypted via
      `Secret_Cipher`) and complete a **Connect Google** flow that stores a refresh
      token; a connection-status indicator reflects connected/expired/error.
- [ ] A capability-checked client fetches top URLs by GA4 pageviews and/or GSC
      clicks for a configurable window, maps them to published post IDs, and feeds
      them into the Blog/Top promoted set.
- [ ] Google sourcing slots in **above** the ticket-402 proxy tiers: connected +
      data → use it; otherwise fall through to comments → Jetpack → newest. No
      empty promoted surface in any case.
- [ ] All Google HTTP access is behind injected transports; report-shaping and
      row→post mapping logic is pure and PHPUnit-covered. No live Google call in
      the unit suite.
- [ ] Tokens/secrets are encrypted at rest; nothing secret is logged or rendered.
- [ ] `composer qa` passes, including deptrac/phparkitect layering for the new
      Google adapter (Core must not depend on it).
- [ ] Documented setup: required Google Cloud OAuth client, redirect URI to
      register, scopes requested, and the GA4 property / GSC site to target.

## Out of scope

- Real-time / per-request Google calls. Top-content is fetched on a schedule
  (cron/transient-cached), not on page render.
- Paid Google API quota management beyond basic caching + graceful degradation.
- Snowflake / Adobe / non-Google analytics.
- News-mode sourcing — Google sourcing targets the Blog/Top promoted set only.

## Dependencies

- **Blocks:** none.
- **Blocked by:** 402 (this layers a new top tier onto 402's fallback seam; build
  402 first so there is always a graceful floor).
- **External:** a Google Cloud project + OAuth client (ID/secret/redirect URI); a
  GA4 property and/or verified GSC site for the target domain; access to
  `cannyforge-lead-capture` / `solar-form` source to lift the OAuth code.

## Approach (sketch — firm up in spike)

1. **Spike:** decide shared library vs vendored copy of `Google_Oauth_Client` +
   `Token_Store` + `Secret_Cipher`; pick GA4 and/or GSC for v1; sketch the admin
   connect UX.
2. Port/lift the OAuth connect flow (the genuinely new part) from `solar-form`.
3. Build a `GoogleTopContentSource` adapter (GA4/GSC report → top post IDs),
   transport-injected, behind an interface like ticket 402's Jetpack adapter.
4. Insert it as the top tier of the Blog/Top fallback chain; cache results via the
   existing `ArchiveCache`/transient pattern on a schedule.

## Notes / decisions log

- 2026-06-23 — Filed off the back of the empty-state work (401/402). Read-only
  exploration of `cannyforge-lead-capture` confirmed the OAuth **token**
  infrastructure is ~10/10 liftable; the OAuth **connect** flow is the main net-new
  build (lead-capture lacks it; `solar-form` has a template). Scopes needed:
  `analytics.readonly` (GA4) and/or `webmasters.readonly` (GSC) — different from
  lead-capture's `gmail.send`/`spreadsheets`, but the same OAuth machinery.
- 2026-06-23 — Kept exploratory: scope is real but the connect-flow port + Google
  Cloud setup make this L→XL; gate the build on the design spike and product
  sign-off rather than starting blind.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked (or the ticket is explicitly split —
   e.g. spike closed, build re-filed).
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
