<?php
/**
 * Resolves the archive endpoint's canonical URL and the URLs derived from it.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Single source of truth for how the plugin's three related archive URLs fit
 * together, so the relationship is documented and tested once instead of
 * re-implemented (with subtle differences) at every call site.
 *
 * - **The archive endpoint** ({@see self::endpoint_url()}) is the plugin's own
 *   rewrite-endpoint URL (e.g. `https://example.com/archive/`). It is always
 *   derived from the configured slug and is never itself overridable — it is
 *   the URL WordPress actually serves the rendered archive at.
 * - **`archive_url`** ({@see Settings::archive_url()}) is an optional "View
 *   Archive" link *destination* override. It answers "where should a link
 *   inviting the visitor to see more content point?" and may legitimately
 *   point anywhere, including off-site. {@see self::destination_url()}
 *   resolves it: the configured override when set, otherwise the endpoint
 *   URL. This is used for the pagination-replacement "View Archive" link, the
 *   admin settings preview link, and — because a non-canonical request to the
 *   endpoint itself (e.g. `/archive/unwanted-tail/`) should send the visitor
 *   to wherever "the archive" now canonically lives — the endpoint's own
 *   redirect for a non-empty tail.
 * - **The SEO canonical override** ({@see \CannyForge\Archive\Contracts\Settings\Seo::canonical()})
 *   is a distinct concept: it answers "which URL should search engines treat
 *   as authoritative for the archive page?" Redirecting visitors elsewhere for
 *   pagination purposes does not change which URL *is* the archive for SEO
 *   purposes, so this override is resolved independently of `archive_url` (see
 *   {@see \CannyForge\Archive\Core\Seo\HeadTagBuilder}, which already owns that
 *   override/fallback decision) — but it shares the same endpoint URL as its
 *   fallback, supplied via {@see self::endpoint_url()}.
 */
class ArchiveUrlResolver {
	/**
	 * The default URL slug for the archive endpoint. Mirrored by
	 * {@see \CannyForge\Archive\Frontend\ArchivePage::DEFAULT_SLUG}; kept here,
	 * in Core, so Admin code can resolve archive URLs without depending on
	 * Frontend.
	 */
	public const DEFAULT_SLUG = 'archive';

	/**
	 * The endpoint slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Construct the resolver.
	 *
	 * @param string $slug Endpoint slug (empty falls back to the default).
	 */
	public function __construct( string $slug = self::DEFAULT_SLUG ) {
		$this->slug = '' !== $slug ? $slug : self::DEFAULT_SLUG;
	}

	/**
	 * The archive's own rewrite-endpoint URL, e.g. `https://example.com/archive/`.
	 *
	 * @return string
	 */
	public function endpoint_url(): string {
		return home_url( '/' . $this->slug . '/' );
	}

	/**
	 * The rewrite-endpoint slug (never empty).
	 *
	 * @return string
	 */
	public function slug(): string {
		return $this->slug;
	}

	/**
	 * Where a "View Archive" link — or a non-canonical request to the endpoint
	 * itself — should send the visitor: the configured `archive_url` override,
	 * or the endpoint URL.
	 *
	 * @param Settings $settings Current settings.
	 * @return string
	 */
	public function destination_url( Settings $settings ): string {
		$override = $settings->archive_url();

		return '' !== $override ? $override : $this->endpoint_url();
	}
}
