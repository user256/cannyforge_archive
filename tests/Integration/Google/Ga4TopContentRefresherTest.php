<?php
/**
 * Tests for the GA4 top-content refresher.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4Client;
use CannyForge\Archive\Integration\Google\Ga4TopContentRefresher;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * GA4 rows are mapped to clean local published post IDs.
 */
class Ga4TopContentRefresherTest extends TestCase {
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
	 * Row→post mapping resolves page paths, filters non-publish IDs, de-duplicates, and caps.
	 *
	 * @return void
	 */
	public function test_map_rows_to_post_ids_filters_and_caps(): void {
		$client    = $this->client( static fn (): ?array => null );
		$refresher = new Ga4TopContentRefresher(
			$client,
			new Ga4CacheStore(),
			$this->settings_store()
		);

		$ids = $refresher->map_rows_to_post_ids(
			array(
				array( 'dimensionValues' => array( array( 'value' => '/a/' ) ) ),
				array( 'dimensionValues' => array( array( 'value' => '/a/' ) ) ),
				array( 'dimensionValues' => array( array( 'value' => '/b/' ) ) ),
				array( 'dimensionValues' => array( array( 'value' => '/c/' ) ) ),
			),
			$client,
			static function ( string $url ): int {
				return match ( $url ) {
					'/a/' => 10,
					'/b/' => 7,
					'/c/' => 12,
					default => 0,
				};
			},
			static function ( int $post_id ): string {
				return 12 === $post_id ? 'draft' : 'publish';
			},
			2
		);

		$this->assertSame( array( 10, 7 ), $ids );
	}

	/**
	 * Refreshing persists the mapped IDs into the GA4 cache store.
	 *
	 * @return void
	 */
	public function test_refresh_saves_mapped_ids_to_cache(): void {
		$cache  = new Ga4CacheStore();
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );

		$client    = new Ga4Client(
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
		);
		$refresher = new Ga4TopContentRefresher(
			$client,
			$cache,
			$this->settings_store(),
			static function ( string $url ): int {
				return '/a/' === $url ? 10 : 7;
			},
			static fn (): string => 'publish'
		);

		$ids = $refresher->refresh( 10 );

		$this->assertSame( array( 10, 7 ), $ids );
		$this->assertSame( array( 10, 7 ), $cache->get_post_ids() );
		$this->assertSame( array( '/a/', '/b/' ), $cache->get_source_urls() );
	}

	/**
	 * Build a GA4 client with the given HTTP transport.
	 *
	 * @param callable $http HTTP transport.
	 * @return Ga4Client
	 */
	private function client( callable $http ): Ga4Client {
		return new Ga4Client(
			new GoogleOauthClient( new GoogleTokenStore(), 'client-id', 'client-secret', static fn (): ?array => null ),
			$http,
			static fn (): string => '2026-06-23'
		);
	}

	/**
	 * Build a Google settings store with a configured GA4 property.
	 *
	 * @return GoogleSettingsStore
	 */
	private function settings_store(): GoogleSettingsStore {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'client-id', 'client-secret', '', 30, '123456789' ) );

		return $store;
	}
}
