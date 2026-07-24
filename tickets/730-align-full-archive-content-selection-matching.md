# Ticket 730: Make full-archive content-selection matching match page-one semantics

**Sprint:** 7 — Modernisation / submission hardening
**Status:** Complete
**Owner:** unassigned
**Estimate:** M
**Priority:** P2 — page-one vs continuation filter divergence

---

## Context

Page one applies `ContentSelector`, which compares category/tag labels with a
normaliser that strips punctuation/spacing and lowercases before intersecting.
Ticket 724 moved continuation eligibility into `FullArchiveQueryArgsBuilder`
`tax_query` / `meta_query` using `field => 'name'` and trimmed-but-exact term
strings.

For the happy path (admin multi-select of exact term names) both usually agree.
They diverge when stored selection values are slugs, differently punctuated
labels, or legacy free-text from earlier settings UI — page one can keep/drop a
post that continuation includes/excludes (or the reverse). That breaks the
product promise that later pages list “remaining eligible” posts under the
**same** content-selection rules.

Noindex detection is already aligned on Yoast / Rank Math keys; term matching is
the gap.

## Goal

A post that survives (or is rejected by) content selection on the promoted first
page is treated identically by full-archive continuation queries.

## Acceptance criteria

- [x] Document the canonical matching rule (exact name, slug, or normalised
      label) and implement it in **both** `ContentSelector` and
      `FullArchiveQueryArgsBuilder` (or share one helper).
- [x] Unit tests cover at least one case that today’s implementations disagree
      on (e.g. stored slug vs term name, or punctuation variants) and prove both
      paths agree after the fix.
- [x] Integration coverage still proves category include/exclude and noindex
      exclusion on continuation pages.
- [x] Existing admin multi-select of term names keeps working without a settings
      migration unless one is explicitly required and tested.

## Out of scope

- Changing pin-ordering on continuation pages (pins remain page-one-only).
- Adding custom taxonomies.

## Dependencies

- **Blocks:** none
- **Blocked by:** 724 (complete in working tree)
- **External:** none

## Notes / decisions log

- 2026-07-24 — Added TermLabelMatcher; ContentSelector and FullArchiveQueryArgsBuilder share case-insensitive exact labels.

- 2026-07-24 — Filed from [plugin-audit-2026-07-24.md](plugin-audit-2026-07-24.md).

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox marked done.
4. Follow-ups filed, not absorbed.
