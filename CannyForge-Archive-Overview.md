# What Is CannyForge Archive?

Most websites have archives.

Most websites also have pagination.

Those two things seem harmless enough. Someone clicks "Next," sees older content, clicks "Next" again, sees even older content, and eventually gives up because they have better things to do.

Humans don't think much about pagination.

Search engines do.

If you've ever looked at a large blog, publisher, or news site, you've seen something like this:

```text
Previous « 1 2 3 4 5 ... » Next
```

Seems innocent.

It isn't.

Every pagination link creates another step between a search engine and your content. On a small site that's no big deal. On a site with thousands or hundreds of thousands of pages, it becomes a problem.

A very big problem.

## Why Pagination Exists

Pagination solves a user interface problem.

Imagine putting 50,000 articles on a single page.

Your browser would catch fire.

So we split content into manageable chunks. Page one contains the newest articles. Page two contains older articles. Page three contains even older articles.

And so on.

For visitors, this works fine.

For search engines, it creates what I like to think of as archive tunnels.

Google lands on page one.

Then page two.

Then page three.

Then page four.

Then page five.

And it keeps walking.

The deeper the content sits in that tunnel, the harder it becomes to discover, crawl, and revisit.

That's click depth.

And click depth matters.

## Why Old Content Becomes Invisible

Imagine publishing one article every day.

Tomorrow's article pushes today's article down the page.

Next week pushes it down again.

Next month pushes it further.

Eventually that article lives on page fifty.

Then page one hundred.

Then page two hundred.

Nothing is technically wrong.

The article still exists.

The URL still works.

But your site architecture is quietly telling search engines:

> This content isn't very important.

Every additional click required to reach a page weakens its position within the site.

The page hasn't changed.

Its visibility has.

## News Sites Have The Opposite Problem

For news publishers, old content isn't usually the goal.

Fresh content is.

The story published three hours ago matters.

The story published three years ago probably doesn't.

Yet traditional archive structures continue feeding internal links into ancient archive pages long after they stop providing value.

The result is a strange situation:

- New stories need authority immediately.
- Older stories keep accumulating archive links.
- Crawl activity gets spent on content nobody cares about anymore.

Google keeps wandering through historical archives while today's headline fights for attention.

That's not ideal.

## Blogs Have A Different Problem

Blogs suffer from almost the exact opposite issue.

For many businesses, the most important content is often the oldest.

The guide that ranks for a valuable keyword.

The tutorial that drives leads every month.

The evergreen resource that keeps generating revenue.

Traditional pagination slowly buries those pages deeper and deeper over time.

Nothing breaks.

They just become harder to reach.

The content remains valuable.

The architecture slowly stops treating it that way.

I call this internal link decay.

The content ages.

The click depth grows.

The internal support shrinks.

## "We'll Just Noindex The Archives"

This is usually where someone says:

> Fine. We'll just noindex paginated pages.

Reasonable idea.

Unfortunately it doesn't solve much.

The archive URLs still exist.

The links still exist.

The crawl paths still exist.

Google still has to process them.

You end up with thousands of archive pages that aren't allowed to rank but still consume crawl resources and still sit in the middle of your site's architecture.

It's a bit like taking all the signs off a road while keeping the road itself.

The traffic doesn't disappear.

## So What Does CannyForge Archive Do?

Instead of encouraging users and crawlers to march endlessly through archive pages, CannyForge Archive creates a dedicated archive destination.

Think of it as a hybrid between:

- an HTML sitemap
- a searchable archive
- a content index
- a discovery hub

Instead of this:

```text
Page 1 → Page 2 → Page 3 → Page 4 → Page 5
```

you get this:

```text
Page 1 → Archive
```

The archive becomes the place where older content lives.

Users can search it.

Filter it.

Browse it.

Find what they need.

Search engines gain a much shorter route to discovering content.

Publishers gain more control over where internal links flow.

Everybody wins.

Well, everybody except page 847 of your category archive.

## What CannyForge Archive Is Not

It isn't another XML sitemap plugin.

It isn't a replacement for your SEO plugin.

It isn't a fancy search plugin.

Those problems already have solutions.

CannyForge Archive focuses on a different problem:

How content moves through a site over time.

Specifically:

- reducing unnecessary click depth
- improving content discoverability
- reducing reliance on endless pagination
- minimising wasted crawl activity
- helping important content retain internal visibility

## Do You Need It?

Maybe.

If your site has a few dozen pages, probably not.

If your site has thousands of articles, years of publishing history, large category archives, aggressive noindex pagination, or a constant battle between new content and old content for visibility, then it's worth thinking about.

Most sites obsess over backlinks.

Far fewer pay attention to the structure of their own internal links.

CannyForge Archive exists because internal architecture matters more than most people realise.

https://portent.com/blog/seo/pagination-tunnels-experiment-click-depth.htm
