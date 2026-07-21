<?php
/**
 * Resolves the archive page's single canonical URL.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Seo;

/**
 * The one place the archive's canonical URL is decided.
 *
 * Ticket 615/612 canonical contract: the archive page has exactly one
 * canonical identity, resolved as:
 *
 *   1. {@see Seo::canonical()} — the explicit SEO canonical override, when set.
 *   2. Otherwise, the archive endpoint's own URL (e.g. `home_url('/archive/')`).
 *
 * {@see \CannyForge\Archive\Contracts\Settings\Settings::archive_url()} (the
 * optional "View Archive" pagination-link destination) is a *link to*
 * elsewhere and must never be passed in here as the endpoint URL and must
 * never be treated as a canonical candidate — a pagination destination is not
 * a statement about what this page's own canonical identity is. Ticket 612's
 * ArchiveUrlResolver owns endpoint/destination routing; this class owns the
 * SEO override-or-endpoint decision only.
 *
 * Pure and framework-free so the emitted `<link rel="canonical">` and the
 * provider bridge share one tested SEO-canonical decision.
 */
final class CanonicalUrlResolver {
	/**
	 * Resolve the archive's canonical URL.
	 *
	 * @param Seo    $seo          The archive-page SEO settings.
	 * @param string $endpoint_url The archive endpoint's own URL (never
	 *                             {@see \CannyForge\Archive\Contracts\Settings\Settings::archive_url()}).
	 * @return string
	 */
	public function resolve( Seo $seo, string $endpoint_url ): string {
		return '' !== $seo->canonical() ? $seo->canonical() : $endpoint_url;
	}
}
