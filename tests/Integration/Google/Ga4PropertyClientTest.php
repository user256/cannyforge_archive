<?php
/**
 * Tests for the Google Analytics property-list client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\Ga4PropertyClient;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use PHPUnit\Framework\TestCase;

/** GA4 account summaries are converted into selectable property IDs. */
final class Ga4PropertyClientTest extends TestCase {
	/**
	 * The client lists accessible properties and includes display metadata.
	 *
	 * @return void
	 */
	public function test_lists_accessible_properties(): void {
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$oauth  = new GoogleOauthClient(
			$tokens,
			'client-id',
			'client-secret',
			static function (): ?array {
				self::fail( 'OAuth refresh should not run with a valid cached token.' );
			}
		);
		$client = new Ga4PropertyClient(
			$oauth,
			static function ( string $url, string $access_token ): array {
				self::assertStringContainsString( 'accountSummaries', $url );
				self::assertStringContainsString( 'pageSize=200', $url );
				self::assertSame( 'access-token', $access_token );

				return array(
					'code' => 200,
					'data' => array(
						'accountSummaries' => array(
							array(
								'displayName'       => 'Example account',
								'propertySummaries' => array(
									array(
										'property'    => 'properties/123456789',
										'displayName' => 'Example Analytics',
									),
								),
							),
						),
					),
				);
			}
		);

		$this->assertSame(
			array(
				array(
					'property_id'  => '123456789',
					'display_name' => 'Example Analytics',
					'account_name' => 'Example account',
				),
			),
			$client->list_properties()
		);
	}
}
