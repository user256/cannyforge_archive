<?php
/**
 * Tests for importing Google OAuth client JSON.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Integration\Google;

use CannyForge\Archive\Integration\Google\GoogleClientConfigImporter;
use PHPUnit\Framework\TestCase;

/**
 * Google client JSON is reduced to the plugin's Google settings fields.
 */
class GoogleClientConfigImporterTest extends TestCase {
	/**
	 * The normal Google web-client export is supported.
	 *
	 * @return void
	 */
	public function test_extracts_web_client_credentials(): void {
		$imported = ( new GoogleClientConfigImporter() )->extract(
			(string) json_encode(
				array(
					'web' => array(
						'client_id'     => 'client-id.apps.googleusercontent.com',
						'client_secret' => 'secret-value',
					),
				)
			)
		);

		$this->assertSame(
			array(
				'google_client_id'     => 'client-id.apps.googleusercontent.com',
				'google_client_secret' => 'secret-value',
			),
			$imported
		);
	}

	/**
	 * Installed-app exports are also tolerated.
	 *
	 * @return void
	 */
	public function test_extracts_installed_client_credentials(): void {
		$imported = ( new GoogleClientConfigImporter() )->extract(
			(string) json_encode(
				array(
					'installed' => array(
						'client_id'     => 'installed-client.apps.googleusercontent.com',
						'client_secret' => 'installed-secret',
					),
				)
			)
		);

		$this->assertSame(
			array(
				'google_client_id'     => 'installed-client.apps.googleusercontent.com',
				'google_client_secret' => 'installed-secret',
			),
			$imported
		);
	}

	/**
	 * Invalid JSON or unrelated payloads import nothing.
	 *
	 * @return void
	 */
	public function test_invalid_payload_imports_nothing(): void {
		$this->assertSame( array(), ( new GoogleClientConfigImporter() )->extract( 'not-json' ) );
		$this->assertSame(
			array(),
			( new GoogleClientConfigImporter() )->extract( (string) json_encode( array( 'name' => 'example' ) ) )
		);
	}
}
