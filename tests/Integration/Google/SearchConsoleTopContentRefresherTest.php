<?php
/**
 * Tests for the Search Console top-content refresher.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsoleClient;
use CannyForge\Archive\Integration\Google\SearchConsoleTopContentRefresher;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Search Console rows are mapped to clean local published post IDs.
 */
class SearchConsoleTopContentRefresherTest extends TestCase {
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
	 * Row→post mapping resolves URLs, filters non-publish IDs, de-duplicates, and caps.
	 *
	 * @return void
	 */
	public function test_map_rows_to_post_ids_filters_and_caps(): void {
		$client    = new SearchConsoleClient(
			new GoogleOauthClient( new GoogleTokenStore(), 'client-id', 'client-secret', static fn (): ?array => null ),
			static fn (): ?array => null,
			static fn (): string => '2026-06-23'
		);
		$refresher = new SearchConsoleTopContentRefresher(
			$client,
			new SearchConsoleCacheStore(),
			$this->settings_store()
		);

		$ids = $refresher->map_rows_to_post_ids(
			array(
				array( 'keys' => array( 'https://example.test/a/' ) ),
				array( 'keys' => array( 'https://example.test/a/' ) ),
				array( 'keys' => array( 'https://example.test/b/' ) ),
				array( 'keys' => array( 'https://example.test/c/' ) ),
			),
			$client,
			static function ( string $url ): int {
				return match ( $url ) {
					'https://example.test/a/' => 10,
					'https://example.test/b/' => 7,
					'https://example.test/c/' => 12,
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
	 * Refreshing persists the mapped IDs into the cache store.
	 *
	 * @return void
	 */
	public function test_refresh_saves_mapped_ids_to_cache(): void {
		$cache  = new SearchConsoleCacheStore();
		$tokens = new GoogleTokenStore();
		$tokens->save_access_token( 'access-token', 9999999999 );
		$client    = new SearchConsoleClient(
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
		);
		$refresher = new SearchConsoleTopContentRefresher(
			$client,
			$cache,
			$this->settings_store(),
			static function ( string $url ): int {
				return 'https://example.test/a/' === $url ? 10 : 7;
			},
			static fn (): string => 'publish'
		);

		$ids = $refresher->refresh( 10 );

		$this->assertSame( array( 10, 7 ), $ids );
		$this->assertSame( array( 10, 7 ), $cache->get_post_ids() );
		$this->assertSame( array( 'https://example.test/a/', 'https://example.test/b/' ), $cache->get_source_urls() );
	}

	/**
	 * Build a Google settings store with a configured Search Console site URL.
	 *
	 * @return GoogleSettingsStore
	 */
	private function settings_store(): GoogleSettingsStore {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com', 30 ) );

		return $store;
	}
}
