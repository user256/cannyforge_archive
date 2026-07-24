# Ticket 728: Drop the residual crawl-budget readme tag

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Complete
**Owner:** unassigned
**Estimate:** S
**Priority:** P2 — WordPress.org SEO / spam-guideline optics

---

## Context

Ticket 718 removed PageRank-sculpting language from public copy. `readme.txt`
still declares:

```
Tags: sitemap, archive, pagination, seo, crawl-budget
```

“crawl-budget” is not a description of a user-facing feature; it frames the
plugin as a crawler-manipulation tool. Combined with the `seo` tag, that is the
same class of positioning 718 tried to neutralize, and it is an easy human-review
flag under the directory’s SEO/spam guidance even when body copy is careful.

## Goal

Public listing tags describe discoverable features without implying guaranteed
crawler-budget or ranking outcomes.

## Acceptance criteria

- [x] `readme.txt` `Tags:` no longer includes `crawl-budget` (or any PageRank /
      crawl-budget synonym).
- [x] Replacement tags, if any, name real features (e.g. filters already covered
      by existing tags; avoid stuffing).
- [x] Body copy still matches ticket 718’s neutral positioning.
- [x] Readme validation / Plugin Check readme checks still pass.

## Out of scope

- Changing pagination behaviour.
- Rewriting internal ticket history that still says “crawl-budget”.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none (718 already completed for body copy)
- **External:** WordPress.org SEO / spam plugin guidance

## Notes / decisions log

- 2026-07-24 — Replaced crawl-budget tag with filters.

- 2026-07-24 — Filed from [plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md).

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox marked done.
4. Follow-ups filed, not absorbed.
