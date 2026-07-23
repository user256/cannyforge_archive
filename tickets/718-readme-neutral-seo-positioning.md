# Ticket 718: Replace PageRank-sculpting language in the public listing copy

**Sprint:** 6 — Trust & Scale (submission follow-up)
**Status:** Complete
**Owner:** unassigned
**Estimate:** S
**Priority:** P2 — WordPress.org policy-review risk

---

## Context

The public readme says default pagination "wastes crawl budget and leaks
PageRank" and that the plugin helps "sculpt PageRank". The runtime feature is
an ordinary archive/pagination experience, but this wording can make it look
like the plugin promotes artificial search-rank manipulation, which attracts
review under the WordPress.org SEO and spam guidelines.

## Goal

Public documentation explains the archive and pagination benefits accurately,
without claims of PageRank sculpting, leakage, or guaranteed crawler outcomes.

## Acceptance criteria

- [x] `readme.txt`, plugin description, and any listing-facing documentation
      use neutral, supportable language about archive discovery, clear internal
      navigation, and a compact pagination experience.
- [x] No shipped public copy claims PageRank sculpting/leakage, guaranteed crawl
      budget savings, or a search-ranking outcome.
- [x] The feature description remains clear about what changes: selected
      archive-type pagination can show a configurable limited sequence and a
      View Archive link.
- [x] Readme validation passes.

## Out of scope

- Removing the pagination feature or changing its targeting behaviour.
- Rewriting historical tickets and internal audit records.

## Dependencies

- **Blocks:** 699 (submission readiness)
- **Blocked by:** none
- **External:** WordPress.org Detailed Plugin Guidelines

## Notes / decisions log

- 2026-07-23 — Filed from the pre-submission audit to reduce policy ambiguity,
  not because the existing feature was found to be malicious.
- 2026-07-23 — Replaced ranking and crawl-outcome claims across public metadata,
  readmes, and the product brief with neutral navigation language.

---

## Definition of done

1. All acceptance criteria are checked.
2. Changes are merged to the working branch.
3. The overview checkbox is marked complete.
4. Any new marketing claims receive the same policy review.
