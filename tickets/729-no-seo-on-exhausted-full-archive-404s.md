# Ticket 729: Do not treat exhausted full-archive URLs as indexable archive requests

**Sprint:** 7 — Modernisation / submission hardening
**Status:** Complete
**Owner:** unassigned
**Estimate:** M
**Priority:** P1 — incorrect SEO on real 404 responses

---

## Context

With full-archive pagination enabled, exhausted URLs such as `/archive/page/999/`
correctly end up as HTTP 404 (`ArchivePage::maybe_render_continuation()` sets
`$wp_query->is_404` and `status_header( 404 )`, covered by integration tests).

`SeoHead` still treats any request where the `cannyforge_archive` query var is
**set** as an archive request:

```php
return isset( $wp_query->query_vars[ ArchivePage::QUERY_VAR ] );
```

Out-of-range `/archive/page/N/` tails match `is_continuation_request()`, which
forces `seo()` to `new Seo()` — default **index, follow** — and builds a
canonical pointing at the exhausted URL itself. If the theme’s 404 template
still runs `wp_head` (normal), the plugin can advertise an indexable canonical
for a page that returned 404. With Yoast / Rank Math active, the same values are
fed into those plugins’ filters.

## Goal

SEO head output and third-party SEO bridges run only for successfully rendered
archive / continuation responses, never for 404 or redirected tails.

## Acceptance criteria

- [x] `SeoHead` does not emit robots/description/canonical (and does not override
      Yoast / Rank Math) when the archive query var is set but the request is a
      404 or a non-rendered tail redirect.
- [x] Valid `/archive/` and in-range `/archive/page/N/` responses keep the
      existing ticket-615 / ticket-723 SEO behaviour.
- [x] Unit tests cover: out-of-range continuation ⇒ no archive SEO; in-range
      continuation ⇒ self-canonical continuation URL; page one ⇒ configured SEO.
- [x] Integration or HTTP-level assertion: `/archive/page/999/` remains 404 and
      its body/head does not claim `rel=canonical` to that URL with `index`.

## Out of scope

- Changing which continuation pages exist.
- Broader SEO-plugin support beyond Yoast / Rank Math.

## Dependencies

- **Blocks:** 699 when full-archive pagination ships enabled-by-choice in 0.1.x
- **Blocked by:** none (723–725 behaviour is already in tree)
- **External:** none

## Approach (optional)

Gate `is_archive_request()` on `! is_404()` after continuation handling, or only
flip a request flag when `ArchivePage` actually renders 200 HTML. Prefer one
explicit “this request rendered the archive” signal over parsing the tail twice.

## Notes / decisions log

- 2026-07-24 — SeoHead ignores is_404 archive query vars; continuation tail read without requiring WP_Query.

- 2026-07-24 — Filed from [plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md).

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox marked done.
4. Follow-ups filed, not absorbed.
