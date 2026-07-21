<?php
/**
 * Shared fixture/helpers for the Google connection controller test suite.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use CannyForge\Archive\Tests\WpRedirectException;
use PHPUnit\Framework\TestCase;

/**
 * The controller's behaviour (ticket 614's OAuth flow, ticket 602's
 * capability/nonce/notice coverage) is split across
 * {@see GoogleConnectionControllerConnectTest},
 * {@see GoogleConnectionControllerCallbackTest}, and
 * {@see GoogleConnectionControllerDisconnectTest} to stay under this
 * codebase's PHPMD class-length budget (ticket 602: extract, don't relax the
 * gate). This abstract base holds the fixture reset and construction/
 * assertion helpers they all share.
 */
abstract class GoogleConnectionControllerTestCase extends TestCase {
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
		unset(
			$GLOBALS['cannyforge_test_current_user_can'],
			$GLOBALS['cannyforge_test_current_user_id'],
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
	 * Build a controller wired to a given Google settings snapshot.
	 *
	 * @param GoogleSettings $settings Google settings to seed the store with.
	 * @return GoogleConnectionController
	 */
	protected function controller_with_settings( GoogleSettings $settings ): GoogleConnectionController {
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
	protected function controller_with_tokens( GoogleTokenStore $tokens ): GoogleConnectionController {
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
	protected function assert_redirects( callable $run ): string {
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
	protected function query_param( string $url, string $key ): string {
		$query = (string) parse_url( $url, PHP_URL_QUERY ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		parse_str( $query, $parsed );

		return isset( $parsed[ $key ] ) && is_scalar( $parsed[ $key ] ) ? (string) $parsed[ $key ] : '';
	}
}
