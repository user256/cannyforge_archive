<?php
/**
 * Tests for the manual GA4 cache-refresh admin-post controller.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\Ga4RefreshController;
use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4Client;
use CannyForge\Archive\Integration\Google\Ga4TopContentRefresher;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\WpDieException;
use CannyForge\Archive\Tests\WpRedirectException;
use PHPUnit\Framework\TestCase;

/**
 * Capability/nonce gate and success/failure notice paths for the manual GA4
 * refresh action, with the refresher doubled (a real
 * {@see Ga4TopContentRefresher} wired to a stubbed HTTP transport, mirroring
 * {@see \CannyForge\Archive\Tests\Integration\Google\Ga4TopContentRefresherTest})
 * so no live Google account is ever touched.
 */
class Ga4RefreshControllerTest extends TestCase {
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
		$controller                                  = $this->controller( new GoogleTokenStore(), array() );
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
		$controller                                     = $this->controller( new GoogleTokenStore(), array() );
		$GLOBALS['cannyforge_test_admin_referer_valid'] = false;

		$this->expectException( WpDieException::class );
		$controller->refresh();
	}

	/**
	 * Refreshing when Google is not connected or no GA4 property is
	 * configured redirects with an actionable error notice instead of
	 * attempting a fetch.
	 *
	 * @return void
	 */
	public function test_refresh_without_ga4_configured_reports_error_notice(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller( $tokens, array(), '' );

		$location = $this->assert_redirects( static fn () => $controller->refresh() );

		$this->assertSame( GoogleConnectionController::NOTICE_ERROR, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertNotSame( '', $this->query_param( $location, 'cf_google_notice' ) );
	}

	/**
	 * A configured, connected GA4 refresh succeeds and reports the cached
	 * post count in the success notice.
	 *
	 * @return void
	 */
	public function test_refresh_success_caches_ids_and_reports_success_notice(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$cache     = new Ga4CacheStore();
		$refresher = new Ga4TopContentRefresher(
			new Ga4Client(
				new GoogleOauthClient( $tokens, 'client-id', 'client-secret', static fn (): ?array => null ),
				static function (): ?array {
					return array(
						'code' => 200,
						'data' => array(
							'rows' => array(
								array( 'dimensionValues' => array( array( 'value' => '/a/' ) ) ),
								array( 'dimensionValues' => array( array( 'value' => '/b/' ) ) ),
							),
						),
					);
				},
				static fn (): string => '2026-06-23'
			),
			$cache,
			$this->google_settings_store( '123456789' ),
			static function ( string $url ): int {
				return '/a/' === $url ? 10 : 7;
			},
			static fn (): string => 'publish'
		);

		$controller = new Ga4RefreshController(
			$this->settings_repository(),
			$this->google_settings_store( '123456789' ),
			$tokens,
			$refresher
		);

		$location = $this->assert_redirects( static fn () => $controller->refresh() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertStringContainsString( '2', $this->query_param( $location, 'cf_google_notice' ) );
		$this->assertSame( array( 10, 7 ), $cache->get_post_ids() );
	}

	/**
	 * A failed GA4 fetch (HTTP transport error) still redirects with a
	 * notice — reporting zero cached IDs — rather than dying or losing the
	 * failure silently.
	 *
	 * @return void
	 */
	public function test_refresh_http_failure_reports_zero_ids(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$cache     = new Ga4CacheStore();
		$refresher = new Ga4TopContentRefresher(
			new Ga4Client(
				new GoogleOauthClient( $tokens, 'client-id', 'client-secret', static fn (): ?array => null ),
				static fn (): ?array => null,
				static fn (): string => '2026-06-23'
			),
			$cache,
			$this->google_settings_store( '123456789' )
		);

		$controller = new Ga4RefreshController(
			$this->settings_repository(),
			$this->google_settings_store( '123456789' ),
			$tokens,
			$refresher
		);

		$location = $this->assert_redirects( static fn () => $controller->refresh() );

		$this->assertSame( GoogleConnectionController::NOTICE_SUCCESS, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertSame( array(), $cache->get_post_ids() );
	}

	// -- Helpers -----------------------------------------------------------------

	/**
	 * Build a controller with an empty refresher double (never expected to
	 * be invoked in gate-failure tests).
	 *
	 * @param GoogleTokenStore $tokens          Token store.
	 * @param array<int,mixed> $rows            Unused placeholder (kept for symmetry).
	 * @param string           $ga4_property_id GA4 property ID.
	 * @return Ga4RefreshController
	 */
	private function controller( GoogleTokenStore $tokens, array $rows, string $ga4_property_id = '123456789' ): Ga4RefreshController {
		unset( $rows );

		$refresher = new Ga4TopContentRefresher(
			new Ga4Client(
				new GoogleOauthClient( $tokens, 'client-id', 'client-secret', static fn (): ?array => null ),
				static fn (): ?array => self::fail( 'HTTP transport should not be called.' ),
				static fn (): string => '2026-06-23'
			),
			new Ga4CacheStore(),
			$this->google_settings_store( $ga4_property_id )
		);

		return new Ga4RefreshController(
			$this->settings_repository(),
			$this->google_settings_store( $ga4_property_id ),
			$tokens,
			$refresher
		);
	}

	/**
	 * A Google settings store configured with the given GA4 property ID.
	 *
	 * @param string $ga4_property_id GA4 property ID (empty for "not configured").
	 * @return GoogleSettingsStore
	 */
	private function google_settings_store( string $ga4_property_id ): GoogleSettingsStore {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'client-id', 'client-secret', '', 30, $ga4_property_id ) );

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
