# Sprint 2 Implementation Audit — 2026-06-18

This compares tickets 201–205 against the code currently on the branch and the
local WordPress install at `http://127.0.0.1/archive/`.

## 201 — Archive endpoint lifecycle hardening

Status against actual work: implemented.

- The endpoint now registers activation/deactivation hooks in `cannyforge-archive.php` to flush rewrite rules safely.
- Canonical-tail rejection/redirect hardening was added to `src/Frontend/ArchivePage.php` to redirect non-canonical `/archive/*` endpoint variants.

Assessment: ticket is ready to close.

## 202 — Content selection normalisation

Status against actual work: implemented.

- The `intersects` method in `src/Core/Archive/ContentSelector.php` now normalizes both inputs by lowercasing and stripping non-alphanumeric characters.
- Slug-vs-name, case, and punctuation tolerance are now natively supported.

Assessment: ticket is ready to close.

## 203 — Distributable build helper + ZIP output

Status against actual work: implemented.

- `scripts/install-plugin.sh` builds `dist/cannyforge-archive/` and `dist/cannyforge-archive-0.1.0.zip`.
- The helper uses `.distignore`-driven filtering and supports both build-only and local-install flows.
- Composer scripts and README usage docs were added.
- Local install smoke passed against `/var/www/html`.

Assessment: ticket is ready to close.

## 204 — Front-end theming controls

Status against actual work: implemented.

- Theme settings now exist in the settings model and admin form.
- The front end applies layout and colour variables through archive/pagination rendering and inline CSS.
- Archive styling has been materially upgraded for desktop and mobile.
- PHPUnit, PHPStan, and PHPCS all pass on the branch.

Assessment: ticket is ready to close.

## 205 — Historic-content seeding + archive smoke data

Status against actual work: implemented.

- `scripts/seed-historic-content.sh` seeds dated posts with category/tag/author variation.
- README now documents the seeding flow plus an explicit smoke checklist.
- The local site was reseeded and verified live at `http://127.0.0.1/archive/`.
- Current local fixture state includes 60 generated historic seed posts spanning multiple years.
- The ticket wording has been corrected in practice: archive filtering is client-side JS, not AJAX.

Assessment: ticket is ready to close.
