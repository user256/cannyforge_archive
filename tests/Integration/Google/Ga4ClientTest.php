<?php
/**
 * Tests for the GA4 Data API client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\Ga4Client;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * GA4 queries are shaped and parsed without live HTTP.
 */
class Ga4ClientTest extends TestCase {
	/**
	 * Reset the in-memory option store before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
	}

	/**
	 * The request body uses an inclusive date range, page-path dimension, and views metric.
	 *
	 * @return void
	 */
	public function test_build_request_body_shapes_report(): void {
		$client = $this->client();
		$body   = $client->build_request_body( 30, '2026-06-23', 50 );

		$this->assertSame( '2026-05-25', $body['dateRanges'][0]['startDate'] );
		$this->assertSame( '2026-06-23', $body['dateRanges'][0]['endDate'] );
		$this->assertSame( 'pagePath', $body['dimensions'][0]['name'] );
		$this->assertSame( 'screenPageViews', $body['metrics'][0]['name'] );
		$this->assertTrue( $body['orderBys'][0]['desc'] );
		$this->assertSame( '50', $body['limit'] );
	}

	/**
	 * Page paths are extracted from GA4 rows in order, skipping duplicates/invalid rows.
	 *
	 * @return void
	 */
	public function test_extract_page_urls_skips_invalid_rows(): void {
		$client = $this->client();
		$pages  = $client->extract_page_urls(
			array(
				array( 'dimensionValues' => array( array( 'value' => '/a/' ) ) ),
				array( 'dimensionValues' => array( array( 'value' => '/a/' ) ) ),
				array( 'dimensionValues' => array( array( 'value' => '' ) ) ),
				array(),
				array( 'dimensionValues' => array( array( 'value' => '/b/' ) ) ),
			),
			10
		);

		$this->assertSame( array( '/a/', '/b/' ), $pages );
	}

	/**
	 * Querying top pages uses the Bearer token and the property runReport endpoint.
	 *
	 * @return void
	 */
	public function test_query_top_pages_uses_oauth_and_returns_rows(): void {
		$token_store = new GoogleTokenStore();
		$token_store->save_access_token( 'access-token', 9999999999 );

		$oauth  = new GoogleOauthClient(
			$token_store,
			'client-id',
			'client-secret',
			static function (): ?array {
				self::fail( 'OAuth refresh transport should not run when a cached access token exists.' );
			}
		);
		$client = new Ga4Client(
			$oauth,
			static function ( string $url, string $access_token, array $body ): ?array {
				self::assertStringContainsString( '/properties/123456789:runReport', $url );
				self::assertSame( 'access-token', $access_token );
				self::assertSame( '25', $body['limit'] ?? '' );

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
		);

		$rows = $client->query_top_pages( '123456789', 30, 25 );

		$this->assertCount( 2, $rows );
	}

	/**
	 * A blank property ID short-circuits before any HTTP call.
	 *
	 * @return void
	 */
	public function test_query_top_pages_returns_empty_for_blank_property(): void {
		$client = new Ga4Client(
			new GoogleOauthClient( new GoogleTokenStore(), 'client-id', 'client-secret', static fn (): ?array => null ),
			static function (): ?array {
				self::fail( 'HTTP transport should not run for a blank property ID.' );
			},
			static fn (): string => '2026-06-23'
		);

		$this->assertSame( array(), $client->query_top_pages( '', 30, 25 ) );
	}

	/**
	 * Build a basic client; HTTP is unused in the pure-method tests.
	 *
	 * @return Ga4Client
	 */
	private function client(): Ga4Client {
		return new Ga4Client(
			new GoogleOauthClient( new GoogleTokenStore(), 'client-id', 'client-secret', static fn (): ?array => null ),
			static fn (): ?array => null,
			static fn (): string => '2026-06-23'
		);
	}
}
