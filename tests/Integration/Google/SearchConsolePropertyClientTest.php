<?php
/**
 * Tests for Search Console property enumeration.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyClient;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Property listing uses the connected account and cleans Google's response.
 */
final class SearchConsolePropertyClientTest extends TestCase {
	/**
	 * Reset option-backed token state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
	}

	/**
	 * A successful sites response is sorted and reduced to UI-safe fields.
	 *
	 * @return void
	 */
	public function test_lists_and_sorts_properties(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$client = new SearchConsolePropertyClient(
			new GoogleOauthClient( $tokens, 'client-id', 'client-secret' ),
			static function ( string $url, string $token ): array {
				self::assertSame( 'https://www.googleapis.com/webmasters/v3/sites', $url );
				self::assertSame( 'access-token', $token );
				return array(
					'code' => 200,
					'data' => array(
						'siteEntry' => array(
							array(
								'siteUrl'         => 'https://z.example/',
								'permissionLevel' => 'siteOwner',
							),
							array(
								'siteUrl'         => 'sc-domain:example.com',
								'permissionLevel' => 'siteFullUser',
							),
							array( 'permissionLevel' => 'siteRestrictedUser' ),
						),
					),
				);
			}
		);

		$this->assertSame(
			array(
				array(
					'site_url'   => 'https://z.example/',
					'permission' => 'siteOwner',
				),
				array(
					'site_url'   => 'sc-domain:example.com',
					'permission' => 'siteFullUser',
				),
			),
			$client->list_properties()
		);
		$this->assertSame( '', $client->last_error() );
	}

	/**
	 * HTTP failures are exposed as actionable state and do not produce options.
	 *
	 * @return void
	 */
	public function test_reports_http_failure(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$client = new SearchConsolePropertyClient(
			new GoogleOauthClient( $tokens, 'client-id', 'client-secret' ),
			static fn ( string $url, string $token ): array => array(
				'code' => 403,
				'data' => array(),
			)
		);

		$this->assertSame( array(), $client->list_properties() );
		$this->assertStringContainsString( 'HTTP 403', $client->last_error() );
	}
}
