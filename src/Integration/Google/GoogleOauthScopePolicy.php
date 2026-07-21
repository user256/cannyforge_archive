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
 * Search Console read-only is always requested: it is the primary, always-on
 * Google signal (ticket 405). Analytics (GA4) read-only is requested only
 * when a GA4 property ID is configured, so a Search Console-only install
 * never asks the admin to grant Analytics access it will never use. This is
 * the single source of truth the connect-flow redirect builds its `scope`
 * parameter from.
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
	 * @param GoogleSettings $settings Current Google settings.
	 * @return array<int, string>
	 */
	public static function scopes( GoogleSettings $settings ): array {
		$scopes = array( self::SCOPE_SEARCH_CONSOLE );
		if ( '' !== $settings->ga4_property_id() ) {
			$scopes[] = self::SCOPE_ANALYTICS;
		}

		return $scopes;
	}

	/**
	 * The scopes to request, as a single space-separated OAuth scope string.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @return string
	 */
	public static function scope_string( GoogleSettings $settings ): string {
		return implode( ' ', self::scopes( $settings ) );
	}
}
