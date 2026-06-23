# CannyForge Archive — Product Brief

> Transcribed from the original proposal ([`PLAN.pdf`](PLAN.pdf)). This is the
> standalone-plugin design the tickets are derived from.
>
> **Scope deviation from the original proposal:** the "final version" Snowflake /
> Adobe Analytics integrations for sourcing top Blog URLs are **out of scope**.
> Blog URLs come from manual text entry or CSV import only.

## Overview

Default WordPress pagination does not suit the needs of news sites, primarily
because it wastes crawl budget (and works against a noindex pagination setup).
This plugin better suits user needs and helps sculpt PageRank toward the pages
we care about.

> N.B. This derives from a previous proposal that was never picked up due to the
> unstable / outdated nature of the host theme/site at the time.

## What the plugin creates

- A combined **HTML Sitemap and JS-powered archive**.
- A **replacement for the current noindex pagination** of taxonomy pages.

### Archive / sitemap

The goal is an archive page that doubles as an HTML sitemap, similar to the
ones large news sites (e.g. TechRadar) publish.

### Pagination

Replace the current approach to pagination:

```
Previous  «  [1] 2 3 4 5 … »  Next
```

…with a more limited sequence that links out to the archive:

```
1 2 3 4 5 6 7 8 9   View Archive →
```

## What this achieves

Tied to click-depth and pagination theory: it helps **new articles** on news
sites and **old articles** on blogs to rank. In the news case by reducing the
leak of PageRank to older content; in the blog case by reducing the impact of
link decay (PageRank lost as pages move down archive pages) on the most
important evergreen content.

## How it works — admin settings

The admin page requires:

- A **user-defined pagination limit** (default `1`) — pages shown before the
  archive link.
- A **Blog / News page toggle** — the plugin creates either a News page or a
  Blog page, as defined by the user.

### If Blog

- A text input or CSV import to define the URLs to include
  (`<user-specified number of top URLs, default 100>`).

### Else if News

- Links to all content published in the last
  `<user-specified time range, default 72>` hours.

### Archive link-type toggles

- **Title** (default on)
- **Description** (default off)
- **Featured image** (default off)

### User-defined Search / Filters (binary on/off, client-side via JS)

- Search box
- Category filters
- Tag filters
- Month + Year filters
- Author filters

## Back-end mock-up (from the brief)

```
┌─ WordPress HTML Sitemap Generator ─────────────────────────────┐
│                                                                │
│  HTML Sitemap Generator Settings                               │
│                                                                │
│   [▣]  Create Blog Sitemap        ┌─ Blog URLs to include ──┐  │
│   [2 ⇅] Pagination (default 1)    │ Include up to [100⇅] URLs│ │
│                                   │ ┌─────────────────────┐ │  │
│  Archive Link Types               │ │ http://…/features/  │ │  │
│   [x] Title (default)             │ │ http://…/ufo-…      │ │  │
│   [ ] Description                 │ │ http://…/election-… │ │  │
│   [ ] Feature Image               │ └─────────────────────┘ │  │
│                                   │        [ Save ]         │  │
│  User Filters                     └─────────────────────────┘  │
│   [x] Search Box                                               │
│   [x] Category filters                                         │
│   [x] Tag filters                                              │
│   [x] Month + Year filters                                     │
│   [ ] Author filters                                           │
└────────────────────────────────────────────────────────────────┘
```

(In News mode the same panel reads "Create News Sitemap" and the right-hand
panel becomes "News Sitemap Settings" with the recent-window control.)

## Confirmed product decisions

These refine and extend the original brief. They are the authoritative scope for
Sprint 1.

1. **Archive / HTML sitemap page** — a dedicated archive page with a search box,
   category / tag / month+year filters, an optional author filter, and
   configurable display fields (Title, Description, Featured Image), as in the
   brief. (Tickets 103, 106.)

2. **Blog mode** — the administrator manually defines which URLs to include, via
   manual URL entry or CSV import. **No analytics integrations.** Explicitly out
   of scope: Snowflake, Adobe Analytics, automatic popularity scoring, and
   traffic-based selection. The admin chooses which URLs appear. (Ticket 105.)

   > **Amendment (ticket 402).** Ticket 105's "no automatic popularity" decision
   > is narrowed for the **empty-list fallback only**: when the curated list is
   > empty, Blog mode falls back to a best-effort top-content set so the archive
   > is never blank. The fallback uses, in strict precedence, the core
   > `comment_count` signal (only when some post has comments) → an *optional*
   > in-process Jetpack Stats read (skipped entirely when Jetpack is absent) →
   > newest. This adds **no external analytics integration and no credentials** —
   > Snowflake/Adobe remain out of scope. Sourcing genuine Google data is
   > tracked separately from the core brief: ticket 403 completed the design
   > spike and chose Search Console as the first implementation slice, with the
   > build split into 404 (OAuth foundation), 405 (Search Console), and 406
   > (optional GA4 follow-on).

3. **News mode** — the archive automatically includes content published within a
   configurable recent window (hours; default 72), updating dynamically by
   publish date. (Ticket 104.)

4. **Pagination replacement** — replace the standard tail
   (`Previous « 1 2 3 4 5 … » Next`) with `1 2 3 4 5 6 7 8 9  View Archive →`.
   The pagination limit is configurable (default 1), the archive-link
   destination is configurable, and it works on supported archive types only.
   (Tickets 107, 109.)

5. **Archive targeting controls** — the administrator chooses where the
   pagination replacement applies, per archive type. Default recommendation:
   Categories **enabled**, Tags **enabled**, Authors **disabled**, Date archives
   **disabled**. (Ticket 109.)

6. **SEO controls** — a dedicated SEO section: archive title, archive meta
   description, index/noindex, follow/nofollow, and a canonical URL override.
   The plugin is fundamentally an SEO / internal-linking tool and needs basic SEO
   configuration. (Ticket 110.)

7. **Content selection controls** — inclusion/exclusion rules: include
   categories/tags, exclude categories/tags, exclude noindex content, and pinned
   URLs that display first. These apply to both News and Blog modes where
   relevant. (Ticket 111.)
