# Ticket 121: Admin UX — CSV import, rename, preview link, real branding

**Sprint:** 1 — Settings & MVP (hardening)
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Review of the deployed admin page against the original mock-up (and direct user
feedback) surfaced gaps: the CSV import from the mock-up was never built (only a
textarea existed), the page branding was a hand-rolled CSS guess rather than the
house style, the name didn't match the product ("Archive Generator"), and there
was no way to jump to the live archive from settings.

## Goal

Bring the admin page in line with the mock-up and the CannyForge house style, and
make it easy to preview the result.

## Acceptance criteria

- [x] **CSV import (Blog mode):** a file-upload field plus an auto-unchecked
      "Replace the list with the CSV (otherwise merge)" checkbox. On save the CSV
      is parsed (first URL-like cell per row, header rows skipped), then merged
      with the textarea list — or, when the box is ticked and the CSV had URLs,
      it replaces the list. Pure `Core\Settings\CsvUrlExtractor`; the upload is
      read only via `is_uploaded_file`. Form is `multipart/form-data`.
- [x] **Rename:** menu label, page title, and heading are now "Archive Generator"
      (was "HTML Sitemap Generator" / "CannyForge Archive").
- [x] **Preview link:** a "Preview archive" button beside Save, opening the live
      archive URL (configured `archive_url` override, else the endpoint) in a new
      tab.
- [x] **Branding:** replicates the sibling `cannyforge-lead-capture` settings
      branding — the bundled CannyForge wordmark SVG with a text fallback, skinned
      to the design system (forge violet, royal-purple Instrument Serif heading) —
      minus the premium upgrade CTA (this plugin has no premium tier).
- [x] `composer qa` green; verified live (CSV merge/replace, rename, preview link,
      wordmark all confirmed on the WordPress 7.0 install).

## Out of scope

- A term-picker for content-selection lists (separate follow-up).
- Drag-to-reorder of pinned URLs.

## Notes / decisions log

- 2026-06-18 — Built from user feedback during the live-smoke session. CSV
  behaviour (merge-by-default + replace override) and the preview-in-new-tab
  behaviour were the user's explicit choices.

---

## Definition of done

This ticket is closeable when:

1. All acceptance criteria above are checked.
2. Changes are merged to the main branch.
3. The corresponding bullet in `tickets/overview.md` is updated.
4. Any follow-up work discovered during implementation is filed as a new ticket.
