# Plugin audit — 2026-07-24

## Scope

Read-only pre-submission audit of the current working tree (including
uncommitted full-archive / filter-bound work for tickets 723–725). No code was
changed in this audit.

Goals:

1. Find anything that might get the plugin rejected by WordPress.org Plugin
   Review.
2. Find logical errors, broken functionality, or obvious problems.

Prior audits already filed 501, 611–615, 707–722. This pass only tickets
**new** residual findings, and notes work already in the working tree that must
still ship.

## Outcome

**Audit tickets 726–731 completed in the working tree (2026-07-24).** Remote
Google Fonts removal shipped with a packaging guard; dead wizard modal/fixture
code removed; crawl-budget tag dropped; SeoHead ignores 404 archive tails;
content-selection matching aligned via `TermLabelMatcher`; full-archive page one
is fragment-cached under a separate `_full` key.

Stale Codex worktrees (`codex/base-wip`, `codex/tickets-715-721`,
`codex/tickets-717-722`, `codex/integration-tickets`, `codex/ticket-723`) had no
commits ahead of `main` and were removed after review — useful 717–725 work was
already on `main` / this working tree.

## Evidence collected

| Check | Result |
|---|---|
| Trialware / license gates | None found |
| Remote static assets (working tree) | Clean — Google Fonts `@import` removed from `assets/css/archive.css` |
| Remote static assets (`HEAD`) | **Fail** — `archive.css` still `@import`s `fonts.googleapis.com` |
| Packaging guard | Working tree adds `test_frontend_css_has_no_remote_imports` |
| External-services disclosure | Present and URL-checks returned 200 for Google policy links |
| `Contributors:` | `user256` — profiles.wordpress.org returns 200 |
| ABSPATH / uninstall guards | Present on shipping PHP |
| Admin nonce + capability | Present on settings / Google admin-post handlers |
| Public search endpoint | Sanitised inputs, nonce, throttle, published posts only |
| Dead code in `src/` | `GoogleWizardModalView` + `GoogleWizardProgressView` (~470 lines) unreferenced; `FixtureEntryProvider` unreferenced |
| readme SEO framing | PageRank language gone (718); `Tags: … crawl-budget` remains |
| Full-archive 404 SEO | `SeoHead::is_archive_request()` is true whenever the query var is set, including exhausted `/archive/page/N/` 404s; continuation path defaults to `index,follow` |

## New tickets

### Goal 1 — WordPress.org rejection risk

- [726 — Ship the remote Google Fonts removal and keep the packaging guard](726-ship-remote-font-css-removal.md)
- [727 — Remove dead Google wizard modal and fixture code from the distributable](727-remove-dead-wizard-modal-and-fixture-code.md)
- [728 — Drop the residual crawl-budget readme tag](728-drop-crawl-budget-readme-tag.md)

### Goal 2 — Logical / functional defects

- [729 — Do not treat exhausted full-archive URLs as indexable archive requests](729-no-seo-on-exhausted-full-archive-404s.md)
- [730 — Make full-archive content-selection matching match page-one semantics](730-align-full-archive-content-selection-matching.md)
- [731 — Fragment-cache page one when full-archive pagination is enabled](731-cache-page-one-with-full-archive-pagination.md)

## Positive findings

- Google OAuth / Search Console / GA4 disclosure in `readme.txt` is detailed and
  policy URLs resolve.
- Direct-file guards, prefixed options/transients, and capability/nonce checks
  on state-changing admin paths look solid.
- Optional Google traffic is opt-in; no phone-home or license gating found.
- Uncommitted 724/725 work bounds continuation and month-option queries; uninstall
  inventory gains the new page-one ID transients.
- Public AJAX search remains nonce-bound and published-content-only.

## Residual notes (not ticketed)

- Plugin header has no `Author` / `Plugin URI` / `Author URI`. Not a hard reject
  by itself; add if Plugin Check warns on the final ZIP.
- `SeoHead` hard-codes `ArchivePage::DEFAULT_SLUG` when building continuation
  canonicals; harmless while the endpoint slug is fixed to `archive`.
- Search throttle intentionally ignores `X-Forwarded-For` unless the proxy
  rewrites `REMOTE_ADDR` (documented).
- Tickets 699 / 701 / 702 / 704 / 705 remain open programme work and were not
  re-filed here.

## Reference

- [WordPress detailed plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [WordPress common review issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- Prior audits: [plugin-audit-2026-07-21.md](plugin-audit-2026-07-21.md),
  [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md)
- Checklist: [`WordPressAudit.md`](../WordPressAudit.md)
