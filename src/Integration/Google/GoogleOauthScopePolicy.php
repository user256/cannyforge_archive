<?php
/**
 * Least-privilege OAuth scope selection for the Google connect flow.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides which Google OAuth scopes to request for the currently configured
 * features (ticket 614).
 *
 * The wizard can request Search Console, Analytics, or both. The settings-page
 * connect flow keeps the legacy Search Console default and adds Analytics when
 * a GA4 property is already configured.
 */
final class GoogleOauthScopePolicy {
	/**
	 * Search Console read-only scope.
	 */
	public const SCOPE_SEARCH_CONSOLE = 'https://www.googleapis.com/auth/webmasters.readonly';

	/**
	 * Analytics (GA4) read-only scope.
	 */
	public const SCOPE_ANALYTICS = 'https://www.googleapis.com/auth/analytics.readonly';

	/**
	 * The individual OAuth scopes to request, in request order.
	 *
	 * @param GoogleSettings $settings           Current Google settings.
	 * @param bool           $include_analytics  Request Analytics access.
	 * @param bool           $include_search_console Request Search Console access.
	 * @return array<int, string>
	 */
	public static function scopes( GoogleSettings $settings, bool $include_analytics = false, bool $include_search_console = true ): array {
		$scopes = array();
		if ( $include_search_console ) {
			$scopes[] = self::SCOPE_SEARCH_CONSOLE;
		}
		if ( $include_analytics || '' !== $settings->ga4_property_id() ) {
			$scopes[] = self::SCOPE_ANALYTICS;
		}

		return $scopes;
	}

	/**
	 * The scopes to request, as a single space-separated OAuth scope string.
	 *
	 * @param GoogleSettings $settings           Current Google settings.
	 * @param bool           $include_analytics  Request Analytics access.
	 * @param bool           $include_search_console Request Search Console access.
	 * @return string
	 */
	public static function scope_string( GoogleSettings $settings, bool $include_analytics = false, bool $include_search_console = true ): string {
		return implode( ' ', self::scopes( $settings, $include_analytics, $include_search_console ) );
	}
}
