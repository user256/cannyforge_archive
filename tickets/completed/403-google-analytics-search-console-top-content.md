# Ticket 403: Google GA4 / Search Console "top content" sourcing (exploratory)

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Done (spike complete; build split into 404–406)
**Owner:** unassigned
**Estimate:** L (spike only; build re-filed)

---

## Context

Ticket 402 added a safe proxy fallback for Blog mode (comments → Jetpack →
newest), but genuine popularity still lives outside WordPress in Google:
Search Console and GA4. Ticket 403 was intentionally filed as an exploratory
spike because the hard part is not the report mapping itself; it is the secure
OAuth connect flow, storage model, layering, and the decision about which Google
signal should ship first.

The sibling repos available locally on 2026-06-23
(`../cannyforge-lead-capture` and `../solar-form`) confirmed that the token
refresh/storage machinery is highly reusable, while the connect/callback flow
must be ported and adapted.

## Goal

Turn the open-ended Google analytics idea into an implementation-ready design,
with explicit scope, architecture, and follow-up tickets.

## Acceptance criteria

- [x] The spike records a shared-library vs vendored-copy decision for the
      liftable Google OAuth/token classes.
- [x] The spike picks the first Google source to ship and states why.
- [x] The spike defines where the Google code should live in this repo's layer
      model so Core does not depend on Google-specific code.
- [x] The spike defines how secrets/tokens are stored and what does **not**
      belong in the existing `Settings` option.
- [x] The spike defines the fetch/cache model so page renders never call Google
      directly.
- [x] The remaining build is split into concrete follow-up tickets rather than
      left as one XL exploratory item.
- [x] `tickets/overview.md` and the relevant planning note are updated to match
      the split.

## Out of scope

- Shipping a live Google integration.
- Porting the connect flow in this ticket.
- Adding Search Console or GA4 clients in this ticket.
- Any page-render-time Google API call.

## Dependencies

- **Blocks:** 404, 405, 406
- **Blocked by:** none
- **External:** Google Cloud OAuth client, verified Search Console property
  and/or GA4 property, plus real site credentials for live verification

## Spike outcome / sign-off

### 1. Reuse strategy: **vendored copy now, shared package later if needed**

Do **not** stop to extract a cross-plugin package first.

- Lift the proven pieces from `cannyforge-lead-capture` into this repo:
  `Google_Oauth_Client`, `Token_Store`, and `Secret_Cipher`.
- Rename them into this plugin's namespace and coding style.
- Keep the code minimal: only the Google pieces archive actually needs.
- Revisit extraction only if a third consumer appears; doing it now would add
  package/release overhead without reducing current delivery risk.

### 2. First source to ship: **Search Console first, GA4 second**

V1 should ship **Search Console**, not GA4.

- It is the better fit for an SEO/internal-linking plugin: clicks and
  impressions from search are a defensible "top content" signal.
- It maps more naturally to canonical page URLs returned in the `page`
  dimension.
- Site-level setup is simpler to explain than GA4 property selection.
- GA4 remains useful, but its property-scoping and page-dimension choices make
  it a clear follow-on once the OAuth foundation exists.

### 3. Layering decision: add an **Integration** layer

Do not put Google clients directly into `Core`.

- Add `src/Integration/Google/*` for OAuth, token stores, HTTP clients, report
  mappers, and cached Google popularity sources.
- `Core` continues to know only the `PopularPostsSource` contract.
- `Bootstrap` wires a Google source into `BlogEntryProvider`; `Admin` uses the
  same Integration layer for connect/disconnect/refresh flows.
- Deptrac should gain an `Integration` layer allowed to depend on `Contracts`
  and `Core`; `Admin` and `Bootstrap` may depend on `Integration`.

### 4. Storage model: **dedicated Google option keys, encrypted secret/token storage**

Do **not** store Google secrets or tokens inside
`cannyforge_archive_settings`.

- Client secret and refresh token must be encrypted at rest via
  `Secret_Cipher`.
- Access token and expiry can be cached in dedicated options.
- Connection status should live in dedicated options as well
  (`connected|disconnected|expired|error`).
- Non-secret Google config (client id, Search Console site URL, report window,
  maybe source preference later) should live in a dedicated Google settings
  store, not the main archive `Settings` value object.

Reason: the archive `Settings` object is currently a pure render/config snapshot;
mixing secret-bearing integration state into it would weaken separation and make
testing/persistence more brittle than necessary.

### 5. Runtime model: **scheduled refresh + cached IDs only**

No page render should call Google.

- A refresh job fetches Google report rows on a schedule and maps them to local
  post IDs up front.
- The page-render path reads only cached IDs through a
  `PopularPostsSource` implementation.
- Refresh should run:
  1. immediately after a successful OAuth connect,
  2. after relevant Google settings change,
  3. on a recurring WP-Cron schedule.

### 6. Blog fallback precedence change

403 should not replace the 402 proxy tiers; it should sit above them.

Recommended precedence:

1. Google cached IDs (Search Console in v1) when configured, connected, and
   non-empty
2. Most-commented posts, only when a post actually has comments
3. Jetpack Stats IDs when available
4. Newest posts

That means `BlogEntryProvider` needs a small refactor in the follow-up build:
Google cannot be injected as the current single "tier 2" popularity source,
because that seam sits **below** comments today.

### 7. Admin UX decision

Keep Google configuration on the existing Archive Generator settings page, in
the Blog-mode panel, not on a separate submenu.

The UI should include:

- Client ID
- Client Secret
- Search Console site URL
- Report window (days)
- Connection status
- `Connect Google`
- `Disconnect`
- `Refresh now`

## Follow-up implementation split

- [404 — Google OAuth foundation + secure settings for top-content sourcing](../404-google-oauth-foundation-secure-settings.md)
- [405 — Search Console cached top-content source for Blog fallback](../405-search-console-cached-top-content-source.md)
- [406 — GA4 top-content source (optional second Google signal)](../406-ga4-top-content-source.md)

## Notes / decisions log

- 2026-06-23 — Local read-only inspection confirmed that
  `../cannyforge-lead-capture/src/Premium/Google/Google_Oauth_Client.php`,
  `Token_Store.php`, and `../cannyforge-lead-capture/src/Core/Security/Secret_Cipher.php`
  are directly liftable in concept, but archive needs its own namespace and a
  slimmer surface.
- 2026-06-23 — The Google connect/callback flow is **not** present in lead
  capture's new service classes; the practical template is the older
  `../solar-form/solar-lead-capture/includes/class-solar-lead-capture-mail-oauth.php`
  admin-post flow.
- 2026-06-23 — Search Console chosen as v1; GA4 deferred to a follow-up ticket.
- 2026-06-23 — The exploratory ticket is closed by split: build work moved to
  404–406 so the next coding slices are reviewable and independently shippable.

---

## Definition of done

This spike ticket is closeable because:

1. The exploratory decisions above are explicit.
2. The remaining build work is re-filed as concrete tickets.
3. `tickets/overview.md` now reflects the split.
4. The implementation work is no longer hidden inside this exploratory ticket.
