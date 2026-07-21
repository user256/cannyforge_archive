<?php
/**
 * Tests for the manual Search Console cache-refresh admin-post controller.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Admin\SearchConsoleRefreshController;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsoleClient;
use CannyForge\Archive\Integration\Google\SearchConsoleTopContentRefresher;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\WpDieException;
use CannyForge\Archive\Tests\WpRedirectException;
use PHPUnit\Framework\TestCase;

/**
 * Capability/nonce gate and success/failure notice paths for the manual
 * Search Console refresh action, with the refresher doubled (a real
 * {@see SearchConsoleTopContentRefresher} wired to a stubbed HTTP transport,
 * mirroring
 * {@see \CannyForge\Archive\Tests\Integration\Google\SearchConsoleTopContentRefresherTest})
 * so no live Google account is ever touched.
 */
class SearchConsoleRefreshControllerTest extends TestCase {
	/**
	 * Reset in-memory WordPress state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		$_GET = array();
		unset(
			$GLOBALS['cannyforge_test_current_user_can'],
			$GLOBALS['cannyforge_test_admin_referer_valid']
		);
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

	/**
	 * The refresh action refuses to run for a user lacking the required
	 * capability.
	 *
	 * @return void
	 */
	public function test_refresh_refused_without_capability(): void {
		$controller                                  = $this->controller( new GoogleTokenStore() );
		$GLOBALS['cannyforge_test_current_user_can'] = false;

		$this->expectException( WpDieException::class );
		$controller->refresh();
	}

	/**
	 * The refresh action refuses to run without a valid nonce.
	 *
	 * @return void
	 */
	public function test_refresh_refused_without_valid_nonce(): void {
		$controller                                     = $this->controller( new GoogleTokenStore() );
		$GLOBALS['cannyforge_test_admin_referer_valid'] = false;

		$this->expectException( WpDieException::class );
		$controller->refresh();
	}

	/**
	 * Refreshing when Google is not connected or no Search Console site URL
	 * is configured redirects with an actionable error notice instead of
	 * attempting a fetch.
	 *
	 * @return void
	 */
	public function test_refresh_without_site_configured_reports_error_notice(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller( $tokens, '' );

		$location = $this->assert_redirects( static fn () => $controller->refresh() );

		$this->assertSame( GoogleConnectionController::NOTICE_ERROR, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertNotSame( '', $this->query_param( $location, 'cf_google_notice' ) );
	}

	/**
	 * A configured, connected Search Console refresh succeeds and reports
	 * the cached post count in the success notice.
	 *
	 * @return void
	 */
	public function test_refresh_success_caches_ids_and_reports_success_notice(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$cache     = new SearchConsoleCacheStore();
		$refresher = new SearchConsoleTopContentRefresher(
			new SearchConsoleClient(
				new GoogleOauthClient( $tokens, 'client-id', 'client-secret', static fn (): ?array => null ),
				static function (): ?array {
					return array(
						'code' => 200,
						'data' => array(
							'rows' => array(
								array( 'keys' => array( 'https://example.test/a/' ) ),
								array( 'keys' => array( 'https://example.test/b/' ) ),
							),
						),
					);
				},
				static fn (): string => '2026-06-23'
			),
			$cache,
			$this->google_settings_store( 'sc-domain:example.com' ),
			static function ( string $url ): int {
				return 'https://example.test/a/' === $url ? 10 : 7;
			},
			static fn (): string => 'publish'
		);

		$controller = new SearchConsoleRefreshController(
			$this->settings_repository(),
			$this->google_settings_store( 'sc-domain:example.com' ),
			$tokens,
			$refresher
		);

		$location = $this->assert_redirects( static fn () => $controller->refresh() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertStringContainsString( '2', $this->query_param( $location, 'cf_google_notice' ) );
		$this->assertSame( array( 10, 7 ), $cache->get_post_ids() );
	}

	/**
	 * A failed Search Console fetch (HTTP transport error) still redirects
	 * with a notice — reporting zero cached IDs — rather than dying or
	 * losing the failure silently.
	 *
	 * @return void
	 */
	public function test_refresh_http_failure_reports_zero_ids(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$cache     = new SearchConsoleCacheStore();
		$refresher = new SearchConsoleTopContentRefresher(
			new SearchConsoleClient(
				new GoogleOauthClient( $tokens, 'client-id', 'client-secret', static fn (): ?array => null ),
				static fn (): ?array => null,
				static fn (): string => '2026-06-23'
			),
			$cache,
			$this->google_settings_store( 'sc-domain:example.com' )
		);

		$controller = new SearchConsoleRefreshController(
			$this->settings_repository(),
			$this->google_settings_store( 'sc-domain:example.com' ),
			$tokens,
			$refresher
		);

		$location = $this->assert_redirects( static fn () => $controller->refresh() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertSame( array(), $cache->get_post_ids() );
	}

	// -- Helpers -----------------------------------------------------------------

	/**
	 * Build a controller with a refresher double that fails the test if its
	 * HTTP transport is ever invoked (used by gate-failure tests, where the
	 * capability/nonce check must short-circuit before any fetch).
	 *
	 * @param GoogleTokenStore $tokens        Token store.
	 * @param string           $site_url      Search Console site URL.
	 * @return SearchConsoleRefreshController
	 */
	private function controller( GoogleTokenStore $tokens, string $site_url = 'sc-domain:example.com' ): SearchConsoleRefreshController {
		$refresher = new SearchConsoleTopContentRefresher(
			new SearchConsoleClient(
				new GoogleOauthClient( $tokens, 'client-id', 'client-secret', static fn (): ?array => null ),
				static fn (): ?array => self::fail( 'HTTP transport should not be called.' ),
				static fn (): string => '2026-06-23'
			),
			new SearchConsoleCacheStore(),
			$this->google_settings_store( $site_url )
		);

		return new SearchConsoleRefreshController(
			$this->settings_repository(),
			$this->google_settings_store( $site_url ),
			$tokens,
			$refresher
		);
	}

	/**
	 * A Google settings store configured with the given Search Console site URL.
	 *
	 * @param string $site_url Search Console site URL (empty for "not configured").
	 * @return GoogleSettingsStore
	 */
	private function google_settings_store( string $site_url ): GoogleSettingsStore {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'client-id', 'client-secret', $site_url, 30 ) );

		return $store;
	}

	/**
	 * An archive settings repository with a small blog URL cap so the
	 * refresh limit is deterministic.
	 *
	 * @return OptionsSettingsRepository
	 */
	private function settings_repository(): OptionsSettingsRepository {
		$repository = new OptionsSettingsRepository();
		$repository->save( Settings::from_array( array( 'blog_max_urls' => 10 ) ) );

		return $repository;
	}

	/**
	 * Run a callable expected to redirect (via the wp_safe_redirect shim)
	 * and return the redirect target.
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
