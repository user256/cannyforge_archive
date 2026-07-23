# Ticket 709: Replace unfinished News panel placeholder copy

**Sprint:** 6 — Trust & Scale (wp.org follow-up)
**Status:** Completed
**Owner:** unassigned
**Estimate:** S
**Priority:** P2 — unfinished UI / review optics

---

## Context

`ModeSettingsPanelView` still renders:

> A list of posts published in the last `<insert newscycle settings>`.

That string is also in `languages/cannyforge-archive.pot`. Reviewers treat
obvious unfinished placeholder copy as a sign the plugin is not ready; it also
fails the “truthful admin UI” bar from ticket 613.

## Goal

News mode description copy is finished, accurate, and free of template
placeholders.

## Acceptance criteria

- [x] The News panel description explains the recent-window behaviour without
      placeholder tokens (`<insert…>`, `TODO`, lorem, etc.).
- [x] Matching `.pot` / translations are regenerated or the stale msgid is
      removed on the next i18n pass (ticket 610 follow-up is acceptable if
      noted).
- [x] A render test asserts the placeholder string is gone.

## Out of scope

- Redesigning the News panel layout.
- Blog-mode description rewrite unless it has the same class of defect.

## Dependencies

- **Blocks:** none (but should land before wp.org screenshots / submission)
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-07-22 — Filed from [google-wizard-audit-2026-07-22.md](google-wizard-audit-2026-07-22.md). Pre-existing string; still shipping in the current tree.
- 2026-07-22 — Replaced the placeholder with finished recent-window copy and updated the POT/test coverage; verified with the full QA suite.

---

## Definition of done

1. Acceptance criteria checked.
2. Merged to the working branch.
3. Overview checkbox for 709 marked done when completed.
4. Follow-ups filed, not absorbed.
