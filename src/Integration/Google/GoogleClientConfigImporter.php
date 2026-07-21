<?php
/**
 * Extracts OAuth client credentials from a downloaded Google JSON file.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supports the JSON payload Google exports for OAuth clients.
 *
 * Google usually wraps web-client credentials under a `web` object. Some apps
 * may expose an `installed` object or a flat payload, so all three shapes are
 * tolerated and reduced to the plugin's posted field names.
 */
final class GoogleClientConfigImporter {
	/**
	 * Extract Google settings fields from a JSON payload.
	 *
	 * @param string $json Uploaded JSON contents.
	 * @return array<string, string>
	 */
	public function extract( string $json ): array {
		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$client = $this->client_payload( $decoded );
		if ( ! is_array( $client ) ) {
			return array();
		}

		$client_id     = $this->string( $client['client_id'] ?? null );
		$client_secret = $this->string( $client['client_secret'] ?? null );
		$imported      = array();

		if ( '' !== $client_id ) {
			$imported['google_client_id'] = $client_id;
		}

		if ( '' !== $client_secret ) {
			$imported['google_client_secret'] = $client_secret;
		}

		return $imported;
	}

	/**
	 * Resolve the most likely Google client object.
	 *
	 * @param array<string, mixed> $decoded Decoded JSON payload.
	 * @return array<string, mixed>
	 */
	private function client_payload( array $decoded ): array {
		if ( isset( $decoded['web'] ) && is_array( $decoded['web'] ) ) {
			return $decoded['web'];
		}

		if ( isset( $decoded['installed'] ) && is_array( $decoded['installed'] ) ) {
			return $decoded['installed'];
		}

		return $decoded;
	}

	/**
	 * Safely coerce a raw value to a trimmed string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
