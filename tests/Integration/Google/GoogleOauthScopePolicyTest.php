<?php
/**
 * Tests for the least-privilege Google OAuth scope policy (ticket 614).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleOauthScopePolicy;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use PHPUnit\Framework\TestCase;

/**
 * Search Console read-only is always requested; Analytics is added only when
 * GA4 is explicitly configured.
 */
class GoogleOauthScopePolicyTest extends TestCase {
	/**
	 * Only the Search Console scope is requested when GA4 is unconfigured.
	 *
	 * @return void
	 */
	public function test_scope_string_is_search_console_only_by_default(): void {
		$settings = new GoogleSettings( 'id', 'secret', 'sc-domain:example.com' );

		$this->assertSame(
			array( GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE ),
			GoogleOauthScopePolicy::scopes( $settings )
		);
		$this->assertSame(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE,
			GoogleOauthScopePolicy::scope_string( $settings )
		);
	}

	/**
	 * Analytics scope is added, after Search Console, only when a GA4
	 * property ID is configured.
	 *
	 * @return void
	 */
	public function test_scope_string_adds_analytics_when_ga4_configured(): void {
		$settings = new GoogleSettings( 'id', 'secret', 'sc-domain:example.com', 30, '123456789' );

		$this->assertSame(
			array( GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE, GoogleOauthScopePolicy::SCOPE_ANALYTICS ),
			GoogleOauthScopePolicy::scopes( $settings )
		);
		$this->assertSame(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE . ' ' . GoogleOauthScopePolicy::SCOPE_ANALYTICS,
			GoogleOauthScopePolicy::scope_string( $settings )
		);
	}

	/**
	 * A GA4 property ID of '' (the default / cleared state) does not request
	 * Analytics, even when other Google settings are configured.
	 *
	 * @return void
	 */
	public function test_scope_string_ignores_blank_ga4_property_id(): void {
		$settings = new GoogleSettings( 'id', 'secret', 'sc-domain:example.com', 30, '' );

		$this->assertSame(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE,
			GoogleOauthScopePolicy::scope_string( $settings )
		);
	}
}
