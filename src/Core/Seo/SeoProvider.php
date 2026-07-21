<?php
/**
 * The set of third-party SEO plugins CannyForge Archive interoperates with.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A supported third-party SEO plugin, or none.
 *
 * Ticket 615: when one of these is active, it already emits its own document
 * title, meta description, robots, and canonical tags on every front-end
 * request (including CannyForge's custom archive endpoint) via its own hook
 * pipeline. {@see \CannyForge\Archive\Frontend\SeoHead} uses this to decide
 * whether to emit its own head fragment directly, or to suppress it and feed
 * its resolved values into the active provider's public filters instead — so
 * the archive never produces two competing canonical/robots tags.
 *
 * Yoast and Rank Math are the two providers selected for this ticket: they are
 * the leading WordPress SEO plugins and the only ones this codebase already
 * has a precedent for recognising (see the `_yoast_wpseo_meta-robots-noindex`
 * / `rank_math_robots` post-meta reads in {@see \CannyForge\Archive\Core\Archive\BlogEntryProvider}
 * and {@see \CannyForge\Archive\Core\Archive\NewsEntryProvider}). Additional
 * providers can be added as new cases plus a detection signal in
 * {@see SeoProviderDetector} and a bridge in {@see \CannyForge\Archive\Frontend\SeoHead}.
 */
enum SeoProvider: string {
	case None     = 'none';
	case Yoast    = 'yoast';
	case RankMath = 'rank_math';
}
