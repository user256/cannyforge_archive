<?php
/**
 * Tests for the Google connection controller's `start_connect()` flow.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\GoogleOauthScopePolicy;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Tests\WpDieException;

/**
 * Least-privilege scope wiring, the capability/nonce gate, and the CSRF
 * state transient `start_connect()` creates — see {@see GoogleConnectionControllerTestCase}
 * for the shared fixture. Pure scope-selection logic is covered directly in
 * {@see \CannyForge\Archive\Tests\Integration\Google\GoogleOauthScopePolicyTest}.
 */
class GoogleConnectionControllerConnectTest extends GoogleConnectionControllerTestCase {
	// -- Scope selection wiring (AC1) -------------------------------------------

	/**
	 * `start_connect()` builds the Google auth redirect with the
	 * least-privilege scope for the current settings.
	 *
	 * @return void
	 */
	public function test_start_connect_requests_search_console_only_scope_by_default(): void {
		$controller = $this->controller_with_settings( new GoogleSettings( 'client-id', 'client-secret' ) );

		$location = $this->assert_redirects( static fn () => $controller->start_connect() );

		$this->assertSame(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE,
			$this->query_param( $location, 'scope' )
		);
	}

	/**
	 * `start_connect()` adds the Analytics scope when GA4 is configured.
	 *
	 * @return void
	 */
	public function test_start_connect_requests_both_scopes_when_ga4_configured(): void {
		$controller = $this->controller_with_settings(
			new GoogleSettings( 'client-id', 'client-secret', '', 30, '999' )
		);

		$location = $this->assert_redirects( static fn () => $controller->start_connect() );

		$this->assertSame(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE . ' ' . GoogleOauthScopePolicy::SCOPE_ANALYTICS,
			$this->query_param( $location, 'scope' )
		);
	}

	// -- Capability / nonce gates (ticket 602) ----------------------------------

	/**
	 * `start_connect()` refuses to run for a user lacking the required
	 * capability: it dies before any state is generated or persisted.
	 *
	 * @return void
	 */
	public function test_start_connect_refused_without_capability(): void {
		$controller                                  = $this->controller_with_settings( new GoogleSettings( 'client-id', 'client-secret' ) );
		$GLOBALS['cannyforge_test_current_user_can'] = false;

		$this->expectException( WpDieException::class );
		$controller->start_connect();
	}

	/**
	 * `start_connect()` refuses to run without a valid nonce, exactly like a
	 * missing/failed capability check — both gates run before any state is
	 * generated.
	 *
	 * @return void
	 */
	public function test_start_connect_refused_without_valid_nonce(): void {
		$controller                                     = $this->controller_with_settings( new GoogleSettings( 'client-id', 'client-secret' ) );
		$GLOBALS['cannyforge_test_admin_referer_valid'] = false;

		$this->expectException( WpDieException::class );
		$controller->start_connect();
	}

	// -- State transient lifecycle: created on connect, consumed once (AC3) ----

	/**
	 * The CSRF state transient `start_connect()` creates is the same one
	 * `handle_callback()` consumes: a full connect → callback round trip
	 * accepts it exactly once, and a second callback replay with that same
	 * state is rejected because the transient was deleted on first use.
	 *
	 * @return void
	 */
	public function test_start_connect_state_is_created_and_consumed_exactly_once_by_callback(): void {
		$tokens = new GoogleTokenStore();
		$store  = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'client-id', 'client-secret' ) );
		$controller = new GoogleConnectionController(
			$store,
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore()
		);

		$connect_location = $this->assert_redirects( static fn () => $controller->start_connect() );
		$state            = $this->query_param( $connect_location, 'state' );

		$this->assertNotSame( '', $state, 'start_connect() must generate a non-empty CSRF state.' );
		$this->assertSame(
			get_current_user_id(),
			get_transient( 'cannyforge_archive_google_oauth_' . $state ),
			'The state transient must be recorded for the current user before redirecting to Google.'
		);

		// First use of the callback with that exact state is accepted (the
		// provider error branch is used here purely to avoid a live token
		// exchange; state validation runs before it).
		$_GET = array(
			'error' => 'access_denied',
			'state' => $state,
		);
		$this->assert_redirects( static fn () => $controller->handle_callback() );

		$this->assertSame( GoogleTokenStore::STATUS_ERROR, $tokens->status() );
		$this->assertFalse(
			get_transient( 'cannyforge_archive_google_oauth_' . $state ),
			'The state transient must be consumed (deleted) after first use.'
		);

		// Replaying the exact same state a second time must be rejected.
		$this->expectException( WpDieException::class );
		$controller->handle_callback();
	}
}
