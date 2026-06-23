<?php
/**
 * Tests for the Search Console API client.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleClient;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Search Console queries are shaped and parsed without live HTTP.
 */
class SearchConsoleClientTest extends TestCase {
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
	 * The request body uses an inclusive date window and page dimension.
	 *
	 * @return void
	 */
	public function test_build_request_body_shapes_date_range(): void {
		$client = $this->client();
		$body   = $client->build_request_body( 30, '2026-06-23', 50 );

		$this->assertSame( '2026-05-25', $body['startDate'] );
		$this->assertSame( '2026-06-23', $body['endDate'] );
		$this->assertSame( array( 'page' ), $body['dimensions'] );
		$this->assertSame( 50, $body['rowLimit'] );
	}

	/**
	 * Page URLs are extracted from rows in order, skipping duplicates/invalid rows.
	 *
	 * @return void
	 */
	public function test_extract_page_urls_skips_invalid_rows(): void {
		$client = $this->client();
		$pages  = $client->extract_page_urls(
			array(
				array( 'keys' => array( 'https://example.test/a/' ) ),
				array( 'keys' => array( 'https://example.test/a/' ) ),
				array( 'keys' => array( '' ) ),
				array(),
				array( 'keys' => array( 'https://example.test/b/' ) ),
			),
			10
		);

		$this->assertSame(
			array(
				'https://example.test/a/',
				'https://example.test/b/',
			),
			$pages
		);
	}

	/**
	 * Querying top pages uses the Bearer token and returns the response rows.
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
		$client = new SearchConsoleClient(
			$oauth,
			static function ( string $url, string $access_token, array $body ): ?array {
				self::assertStringContainsString( '/sites/sc-domain%3Aexample.com/searchAnalytics/query', $url );
				self::assertSame( 'access-token', $access_token );
				self::assertSame( 25, $body['rowLimit'] ?? 0 );

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
		);

		$rows = $client->query_top_pages( 'sc-domain:example.com', 30, 25 );

		$this->assertCount( 2, $rows );
	}

	/**
	 * Build a basic client; HTTP is unused in the pure-method tests.
	 *
	 * @return SearchConsoleClient
	 */
	private function client(): SearchConsoleClient {
		return new SearchConsoleClient(
			new GoogleOauthClient( new GoogleTokenStore(), 'client-id', 'client-secret', static fn (): ?array => null ),
			static fn (): ?array => null,
			static fn (): string => '2026-06-23'
		);
	}
}
