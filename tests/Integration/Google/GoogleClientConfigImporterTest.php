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
 * Only a Web application OAuth client export is accepted; everything else
 * fails with an actionable, non-silent error.
 */
class GoogleClientConfigImporterTest extends TestCase {
	/**
	 * The normal Google Web-client export is supported.
	 *
	 * @return void
	 */
	public function test_imports_web_client_credentials(): void {
		$result = ( new GoogleClientConfigImporter() )->import(
			(string) json_encode(
				array(
					'web' => array(
						'client_id'     => 'client-id.apps.googleusercontent.com',
						'client_secret' => 'secret-value',
					),
				)
			)
		);

		$this->assertTrue( $result->ok() );
		$this->assertSame( '', $result->error() );
		$this->assertSame(
			array(
				'google_client_id'     => 'client-id.apps.googleusercontent.com',
				'google_client_secret' => 'secret-value',
			),
			$result->fields()
		);
	}

	/**
	 * A Web client export with only a Client ID is still a valid partial import.
	 *
	 * @return void
	 */
	public function test_imports_partial_web_client_credentials(): void {
		$result = ( new GoogleClientConfigImporter() )->import(
			(string) json_encode( array( 'web' => array( 'client_id' => 'only-the-id' ) ) )
		);

		$this->assertTrue( $result->ok() );
		$this->assertSame( array( 'google_client_id' => 'only-the-id' ), $result->fields() );
	}

	/**
	 * Installed-app (desktop/mobile) exports are rejected by name: this
	 * plugin only supports the Web application OAuth client type.
	 *
	 * @return void
	 */
	public function test_rejects_installed_client_type(): void {
		$result = ( new GoogleClientConfigImporter() )->import(
			(string) json_encode(
				array(
					'installed' => array(
						'client_id'     => 'installed-client.apps.googleusercontent.com',
						'client_secret' => 'installed-secret',
					),
				)
			)
		);

		$this->assertFalse( $result->ok() );
		$this->assertSame( array(), $result->fields() );
		$this->assertStringContainsString( 'installed-app', $result->error() );
		$this->assertStringContainsString( 'Web application', $result->error() );
	}

	/**
	 * Invalid JSON is rejected with an actionable message, not silently ignored.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_json(): void {
		$result = ( new GoogleClientConfigImporter() )->import( 'not-json' );

		$this->assertFalse( $result->ok() );
		$this->assertNotSame( '', $result->error() );
	}

	/**
	 * A JSON payload that isn't a Google client export (no `web` key, and not
	 * an `installed` client either) is rejected rather than guessed at.
	 *
	 * @return void
	 */
	public function test_rejects_payload_without_a_web_client(): void {
		$result = ( new GoogleClientConfigImporter() )->import( (string) json_encode( array( 'name' => 'example' ) ) );

		$this->assertFalse( $result->ok() );
		$this->assertStringContainsString( 'Web application', $result->error() );
	}

	/**
	 * A `web` object with neither field is rejected: nothing usable was imported.
	 *
	 * @return void
	 */
	public function test_rejects_web_client_without_credentials(): void {
		$result = ( new GoogleClientConfigImporter() )->import( (string) json_encode( array( 'web' => array() ) ) );

		$this->assertFalse( $result->ok() );
		$this->assertStringContainsString( 'did not include', $result->error() );
	}

	/**
	 * An oversized upload is rejected before it is trusted as a genuine
	 * (tiny) Google client export.
	 *
	 * @return void
	 */
	public function test_rejects_oversized_payload(): void {
		$oversized = str_repeat( 'a', GoogleClientConfigImporter::MAX_BYTES + 1 );

		$result = ( new GoogleClientConfigImporter() )->import( $oversized );

		$this->assertFalse( $result->ok() );
		$this->assertStringContainsString( 'too large', $result->error() );
	}
}
