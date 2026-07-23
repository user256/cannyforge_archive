# Google wizard audit — 2026-07-22

## Scope

Read-only review of the uncommitted full-page Google setup wizard work
(GA4 property picker, OAuth state payload, settings → wizard delegation). No
code was changed in this audit.

## Outcome

**Not submission-ready as-is.** The wizard architecture is sound (nonce +
capability gates, `ABSPATH` guards, overlay saves, least-privilege scopes with
a wizard signal), but the current tree has stale External-services disclosure,
incomplete uninstall cleanup relative to the readme claim, and several
wizard-path bugs that break or mislead the GA4 setup flow.

## New tickets

### Goal 1 — WordPress.org rejection risk

- [707 — Refresh External services disclosure for wizard-time Analytics scope + Admin API](707-wporg-google-external-services-disclosure.md)
- [708 — Uninstall must remove user-scoped Google property-list transients](708-uninstall-property-list-transients.md)
- [709 — Replace unfinished News panel placeholder copy](709-news-panel-placeholder-copy.md)

### Goal 2 — Logical / functional defects

- [710 — Wizard must enable Analytics Admin API and surface GA4 list failures](710-ga4-admin-api-instructions-and-errors.md)
- [711 — Re-authorize when upgrading from Search Console-only to SC+GA4](711-wizard-ga4-scope-upgrade-reconnect.md)
- [712 — Keep `cf_signal` on wizard Back / checklist links](712-wizard-signal-navigation.md)
- [713 — Remove leftover Property-step “Save property and continue” CTA](713-wizard-property-duplicate-save.md)

## Positive findings

- New PHP files guard on `ABSPATH`; admin-post handlers check `manage_options`
  and nonces; OAuth state is consumed before token mutation.
- Main settings form no longer posts Google fields; `save_overlay()` prevents
  accidental wipes of unrendered Google options.
- Consent copy is driven from `GoogleOauthScopePolicy`, matching the redirect.
- Client secret is never echoed into the credentials form; blank secret preserves
  the stored value.
- JS no longer depends on a modal save/`fetch` path; copy/select-on-focus are
  covered by unit tests.

## Residual risks (not ticketed)

- Repo-wide `composer qa` was not re-run as part of this audit; treat 611/699
  as still owning the release gate.
- `GoogleWizardPage` constructs an unused `Ga4PropertyClient` (dead dependency).
- Prefixed search-cache / throttle transients remain outside the fixed uninstall
  inventory (pre-existing vs ticket 606 claim) — adjacent to 708 but not new to
  this wizard diff.

## Reference

- [WordPress detailed plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [WordPress common review issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- Prior audit: [plugin-audit-2026-07-21.md](plugin-audit-2026-07-21.md)
