<?php
/**
 * Tests for the GA4 property-list admin-post handler.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\Ga4PropertyController;
use CannyForge\Archive\Integration\Google\Ga4PropertyClient;
use CannyForge\Archive\Integration\Google\Ga4PropertyStore;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use CannyForge\Archive\Tests\WpRedirectException;
use PHPUnit\Framework\TestCase;

/** Failed GA4 list requests are actionable and cannot leave stale choices. */
final class Ga4PropertyControllerTest extends TestCase {
	/** Reset the WordPress shims between tests. */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		TransientStore::reset();
		unset( $GLOBALS['cannyforge_test_current_user_can'] );
		$_POST = array();
	}

	/**
	 * A failed Admin API request is shown in the redirect notice and clears the
	 * previous account's cached property list.
	 *
	 * @return void
	 */
	public function test_failed_load_surfaces_client_error_and_clears_old_cache(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );

		$store = new Ga4PropertyStore();
		$store->save(
			array(
				array(
					'property_id'  => '123',
					'display_name' => 'Old account',
					'account_name' => 'Old account',
				),
			)
		);

		$oauth      = new GoogleOauthClient( $tokens, 'client-id', 'client-secret' );
		$client     = new Ga4PropertyClient(
			$oauth,
			static function (): array {
				return array(
					'code' => 403,
					'data' => array(),
				);
			}
		);
		$controller = new Ga4PropertyController( $tokens, $client, $store );

		try {
			$controller->refresh();
			$this->fail( 'Expected a redirect.' );
		} catch ( WpRedirectException $exception ) {
			$query = array();
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- test-only redirect query parsing.
			$query_string = parse_url( $exception->location, PHP_URL_QUERY );
			parse_str( (string) $query_string, $query );
			$this->assertSame( 'error', $query['cf_google_notice_type'] ?? '' );
			$this->assertStringContainsString( 'HTTP 403', rawurldecode( (string) ( $query['cf_google_notice'] ?? '' ) ) );
		}

		$this->assertSame( array(), $store->get() );
	}
}
