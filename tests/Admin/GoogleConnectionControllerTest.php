<?php
/**
 * Tests for the Google connection controller (ticket 614).
 *
 * Ticket 602 (a dedicated Google admin-controller test harness) has not
 * landed yet, so this suite brings its own minimal admin-post shim
 * (`tests/wp-admin-post-shim.php`, loaded from `tests/bootstrap.php`) scoped
 * to exactly the WordPress primitives this controller touches. It covers the
 * ticket 614 behaviour: least-privilege scope selection, the callback
 * CSRF-state ordering fix, state replay/foreign state rejection, and
 * best-effort remote revocation on disconnect.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\GoogleOauthScopePolicy;
use CannyForge\Archive\Integration\Google\GoogleRevocationService;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use CannyForge\Archive\Tests\WpDieException;
use CannyForge\Archive\Tests\WpRedirectException;
use PHPUnit\Framework\TestCase;

/**
 * Least-privilege scope wiring in `start_connect()`, callback CSRF handling,
 * and disconnect/revocation. Pure scope-selection logic is covered directly
 * in {@see \CannyForge\Archive\Tests\Integration\Google\GoogleOauthScopePolicyTest}.
 */
class GoogleConnectionControllerTest extends TestCase {
	/**
	 * Reset in-memory WordPress state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		TransientStore::reset();
		$_GET = array();
		unset( $GLOBALS['cannyforge_test_current_user_can'], $GLOBALS['cannyforge_test_current_user_id'] );
	}

	/**
	 * Clean up superglobals so tests never leak into each other.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

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

	// -- Callback CSRF-state ordering (AC2) -------------------------------------

	/**
	 * A callback carrying `error` but no CSRF state must not mutate token
	 * status: state is validated before the error branch runs.
	 *
	 * @return void
	 */
	public function test_callback_error_without_state_dies_and_does_not_mutate_status(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		$_GET = array( 'error' => 'access_denied' );

		$this->expectException( WpDieException::class );

		try {
			$controller->handle_callback();
		} finally {
			$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
		}
	}

	/**
	 * A callback whose state transient belongs to a different user is
	 * rejected, and status is not mutated.
	 *
	 * @return void
	 */
	public function test_callback_rejects_foreign_state_and_does_not_mutate_status(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		set_transient( 'cannyforge_archive_google_oauth_foreign-state', 999 );
		$GLOBALS['cannyforge_test_current_user_id'] = 1;
		$_GET                                       = array(
			'error' => 'access_denied',
			'state' => 'foreign-state',
		);

		$this->expectException( WpDieException::class );

		try {
			$controller->handle_callback();
		} finally {
			$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
		}
	}

	/**
	 * A callback with no matching transient at all (missing/expired state) is
	 * rejected the same way as a foreign state.
	 *
	 * @return void
	 */
	public function test_callback_rejects_missing_or_expired_state(): void {
		$controller = $this->controller_with_tokens( new GoogleTokenStore() );

		$_GET = array(
			'code'  => 'auth-code',
			'state' => 'never-issued-state',
		);

		$this->expectException( WpDieException::class );
		$controller->handle_callback();
	}

	/**
	 * A valid state accompanying an `error` callback DOES mutate status to
	 * error and redirects — distinguishing a legitimate provider error from
	 * the CSRF-able path above.
	 *
	 * @return void
	 */
	public function test_callback_error_with_valid_state_sets_error_status(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		set_transient( 'cannyforge_archive_google_oauth_good-state', 1 );
		$_GET = array(
			'error' => 'access_denied',
			'state' => 'good-state',
		);

		$this->assert_redirects( static fn () => $controller->handle_callback() );

		$this->assertSame( GoogleTokenStore::STATUS_ERROR, $tokens->status() );
	}

	/**
	 * A state transient is consumed on first use; replaying the same state
	 * value on a second callback is rejected.
	 *
	 * @return void
	 */
	public function test_callback_state_cannot_be_replayed(): void {
		$controller = $this->controller_with_settings( new GoogleSettings( 'client-id', 'client-secret' ) );

		set_transient( 'cannyforge_archive_google_oauth_one-time-state', 1 );
		$_GET = array(
			'code'  => 'auth-code',
			'state' => 'one-time-state',
		);

		// First use: state is valid, consumed; the token exchange itself then
		// fails in the test runtime (no live Google), which redirects rather
		// than dying — either way, state has been consumed.
		$this->assert_redirects( static fn () => $controller->handle_callback() );

		// Second use of the exact same state must be rejected.
		$this->expectException( WpDieException::class );
		$controller->handle_callback();
	}

	// -- Disconnect / revocation (AC4) ------------------------------------------

	/**
	 * A successful remote revocation clears local state and reports success.
	 *
	 * @return void
	 */
	public function test_disconnect_revokes_remotely_and_clears_local_state(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_refresh_token( 'refresh-token' );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$search_cache = new SearchConsoleCacheStore();
		$search_cache->save_post_ids( array( 1, 2, 3 ) );

		$revocation = new GoogleRevocationService(
			$tokens,
			static function (): ?array {
				return array( 'code' => 200 );
			}
		);

		$controller = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			$search_cache,
			new Ga4CacheStore(),
			$revocation
		);

		$location = $this->assert_redirects( static fn () => $controller->disconnect() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertSame( '', $tokens->refresh_token() );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
		$this->assertSame( array(), $search_cache->get_post_ids() );
	}

	/**
	 * A failed remote revocation still clears local state (idempotent local
	 * cleanup) but reports the failure to the admin.
	 *
	 * @return void
	 */
	public function test_disconnect_still_clears_local_state_when_remote_revocation_fails(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_refresh_token( 'refresh-token' );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$revocation = new GoogleRevocationService(
			$tokens,
			static function (): ?array {
				return null;
			}
		);

		$controller = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore(),
			$revocation
		);

		$location = $this->assert_redirects( static fn () => $controller->disconnect() );

		$this->assertSame( GoogleConnectionController::NOTICE_ERROR, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertSame( '', $tokens->refresh_token() );
		$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
	}

	/**
	 * Disconnecting when already disconnected (no stored tokens) is
	 * idempotent: no remote call is needed and it still reports success.
	 *
	 * @return void
	 */
	public function test_disconnect_is_idempotent_when_already_disconnected(): void {
		$tokens     = new GoogleTokenStore();
		$revocation = new GoogleRevocationService(
			$tokens,
			static function (): ?array {
				self::fail( 'HTTP should not be called when no token is stored.' );
			}
		);

		$controller = new GoogleConnectionController(
			new GoogleSettingsStore(),
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore(),
			$revocation
		);

		$location = $this->assert_redirects( static fn () => $controller->disconnect() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
	}

	// -- Helpers -----------------------------------------------------------------

	/**
	 * Build a controller wired to a given Google settings snapshot.
	 *
	 * @param GoogleSettings $settings Google settings to seed the store with.
	 * @return GoogleConnectionController
	 */
	private function controller_with_settings( GoogleSettings $settings ): GoogleConnectionController {
		$store = new GoogleSettingsStore();
		$store->save( $settings );

		return new GoogleConnectionController(
			$store,
			new GoogleTokenStore(),
			new SearchConsoleCacheStore(),
			new Ga4CacheStore()
		);
	}

	/**
	 * Build a controller wired to a given token store, with a configured
	 * client so callback handling can proceed past the config check.
	 *
	 * @param GoogleTokenStore $tokens Token store.
	 * @return GoogleConnectionController
	 */
	private function controller_with_tokens( GoogleTokenStore $tokens ): GoogleConnectionController {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'client-id', 'client-secret' ) );

		return new GoogleConnectionController(
			$store,
			$tokens,
			new SearchConsoleCacheStore(),
			new Ga4CacheStore()
		);
	}

	/**
	 * Run a callable expected to redirect (via the wp_redirect/wp_safe_redirect
	 * shim) and return the redirect target.
	 *
	 * @param callable $run The controller call under test.
	 * @return string
	 */
	private function assert_redirects( callable $run ): string {
		try {
			$run();
			$this->fail( 'Expected a WpRedirectException.' );
		} catch ( WpRedirectException $e ) {
			return $e->location;
		}
	}

	/**
	 * Read a single query-string parameter from a URL.
	 *
	 * @param string $url URL.
	 * @param string $key Query parameter name.
	 * @return string
	 */
	private function query_param( string $url, string $key ): string {
		$query = (string) parse_url( $url, PHP_URL_QUERY ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		parse_str( $query, $parsed );

		return isset( $parsed[ $key ] ) && is_scalar( $parsed[ $key ] ) ? (string) $parsed[ $key ] : '';
	}
}
