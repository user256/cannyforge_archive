<?php
/**
 * Tests for the dedicated Google settings store.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\SecretCipher;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Google settings are stored separately from the main archive option.
 */
class GoogleSettingsStoreTest extends TestCase {
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
	 * The client secret is encrypted at rest and decrypts on read.
	 *
	 * @return void
	 */
	public function test_save_encrypts_client_secret_and_get_decrypts_it(): void {
		$store = new GoogleSettingsStore( new SecretCipher( 'test-salt' ) );
		$store->save(
			new GoogleSettings(
				'client-id',
				'client-secret',
				'https://example.com/',
				45
			)
		);

		$raw = OptionStore::all()[ GoogleSettingsStore::OPTION_KEY ] ?? array();
		$this->assertIsArray( $raw );
		$this->assertNotSame( 'client-secret', $raw['client_secret'] ?? '' );

		$loaded = $store->get();
		$this->assertSame( 'client-id', $loaded->client_id() );
		$this->assertSame( 'client-secret', $loaded->client_secret() );
		$this->assertSame( 'https://example.com/', $loaded->search_console_site_url() );
		$this->assertSame( 45, $loaded->report_window_days() );
	}

	/**
	 * A blank secret on save preserves the existing stored secret.
	 *
	 * @return void
	 */
	public function test_blank_secret_preserves_existing_secret(): void {
		$store = new GoogleSettingsStore( new SecretCipher( 'test-salt' ) );
		$store->save( new GoogleSettings( 'client-id', 'first-secret', 'https://example.com/', 30 ) );
		$store->save( new GoogleSettings( 'client-id-2', '', 'sc-domain:example.com', 60 ) );

		$loaded = $store->get();
		$this->assertSame( 'client-id-2', $loaded->client_id() );
		$this->assertSame( 'first-secret', $loaded->client_secret() );
		$this->assertSame( 'sc-domain:example.com', $loaded->search_console_site_url() );
		$this->assertSame( 60, $loaded->report_window_days() );
		$this->assertTrue( $store->has_client_secret() );
	}
}
