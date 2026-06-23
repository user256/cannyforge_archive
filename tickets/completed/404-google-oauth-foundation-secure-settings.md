# Ticket 404: Google OAuth foundation + secure settings for top-content sourcing

**Sprint:** 4 — Resilience & empty-state fallbacks
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

Ticket 403 completed the design spike and chose a Google integration shape:
vendored OAuth/token classes, Search Console first, Google code in a dedicated
Integration layer, and encrypted secret/token storage outside the main archive
settings option. This ticket builds that foundation so later tickets can fetch
top-content data without re-solving OAuth and storage.

## Goal

An administrator can configure and securely connect a Google account for the
archive plugin's read-only top-content sourcing.

## Acceptance criteria

- [ ] A new `Integration` layer exists for Google-specific code, and deptrac is
      updated so `Core` does not depend on Google classes.
- [ ] `Secret_Cipher`, `Token_Store`, and `Google_Oauth_Client` (or archive-
      named equivalents) are vendored into this repo with PHPUnit coverage.
- [ ] Google config is stored outside `cannyforge_archive_settings`; client
      secret and refresh token are encrypted at rest.
- [ ] The Archive Generator settings page exposes Google client ID, client
      secret, Search Console site URL, and report-window controls.
- [ ] An admin-post `Connect Google` flow exists: start → consent redirect →
      callback → token exchange → refresh token persisted.
- [ ] An admin-post `Disconnect` flow clears the stored Google connection.
- [ ] The admin UI renders connection status (`disconnected|connected|expired|error`)
      without exposing secrets or tokens.
- [ ] All Google HTTP calls are transport-injected and unit-testable without a
      live Google account.
- [ ] `composer qa` passes.

## Out of scope

- Fetching top URLs from Search Console.
- Blog fallback precedence changes.
- GA4 support.

## Dependencies

- **Blocks:** 405, 406
- **Blocked by:** 403
- **External:** Google Cloud OAuth client credentials and a real redirect URI for
  live verification

## Approach

- Add `src/Integration/Google/*` and wire it from `Bootstrap`.
- Keep non-secret Google config in a dedicated settings store; keep tokens and
  connection state in a dedicated token store.
- Port the admin-post connect/callback pattern from the local `solar-form`
  reference, adapted to archive naming and read-only Google scopes.

## Notes / decisions log

- 2026-06-23 — Filed from the 403 spike. This is the prerequisite slice; do it
  before any Search Console or GA4 report client work.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch (or the sprint's working branch).
3. The corresponding bullet in `tickets/overview.md` is changed from `- [ ]` to
   `- [x]`.
4. Any follow-up work discovered during implementation is filed as a new ticket —
   not silently absorbed.
