# CannyForge Archive — Product Brief

> Transcribed from the original proposal ([`PLAN.pdf`](PLAN.pdf)). This is the
> standalone-plugin design the tickets are derived from.

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

- **MVP:** a text input or CSV import to define URLs to include
  (`<user-specified number of top URLs, default 100>`).
- **Final version:** use Snowflake or Adobe (analytics) to define the URLs to
  include (`<user-specified number of top URLs, default 100>`).

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
