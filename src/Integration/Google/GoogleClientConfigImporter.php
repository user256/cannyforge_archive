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
 * Supports the JSON payload Google exports for a Web application OAuth
 * client (Google Auth Platform > Clients > Download JSON).
 *
 * Only the `web` client shape is accepted. Google also exports an
 * `installed` (desktop/mobile) shape for a different client type; that
 * client type uses a different OAuth flow than this plugin implements; and
 * a payload that carried working `installed` credentials would otherwise
 * fail confusingly (or worse, silently) once the plugin tried to use it as
 * a Web client. Rejecting it up front, by name, keeps the failure honest.
 */
final class GoogleClientConfigImporter {
	/**
	 * Upper bound on an accepted upload. A genuine Google client export is a
	 * few hundred bytes; this is generous headroom while still rejecting
	 * obviously-wrong (or abusive) uploads before they're fully parsed.
	 */
	public const MAX_BYTES = 65536;

	/**
	 * Import Google settings fields from a JSON payload.
	 *
	 * @param string $json Uploaded JSON contents.
	 * @return GoogleClientImportResult
	 */
	public function import( string $json ): GoogleClientImportResult {
		if ( strlen( $json ) > self::MAX_BYTES ) {
			return GoogleClientImportResult::failure(
				__( 'That file is too large to be a Google OAuth client export. Upload the JSON downloaded from Google Auth Platform > Clients.', 'cannyforge-archive' )
			);
		}

		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return GoogleClientImportResult::failure(
				__( 'That file is not valid JSON. Download the client JSON again from Google Auth Platform > Clients and upload it unmodified.', 'cannyforge-archive' )
			);
		}

		if ( isset( $decoded['installed'] ) ) {
			return GoogleClientImportResult::failure(
				__( 'That is an installed-app OAuth client, which this plugin cannot use. In Google Auth Platform > Clients, create (or use) a Web application client and upload its JSON instead.', 'cannyforge-archive' )
			);
		}

		if ( ! isset( $decoded['web'] ) || ! is_array( $decoded['web'] ) ) {
			return GoogleClientImportResult::failure(
				__( 'That JSON does not look like a Google Web application OAuth client export. In Google Auth Platform > Clients, open your Web application client and use Download JSON.', 'cannyforge-archive' )
			);
		}

		return $this->extract_fields( $decoded['web'] );
	}

	/**
	 * Extract the client_id/client_secret fields from a `web` client object.
	 *
	 * @param array<string, mixed> $client The decoded `web` object.
	 * @return GoogleClientImportResult
	 */
	private function extract_fields( array $client ): GoogleClientImportResult {
		$client_id     = $this->string( $client['client_id'] ?? null );
		$client_secret = $this->string( $client['client_secret'] ?? null );

		if ( '' === $client_id && '' === $client_secret ) {
			return GoogleClientImportResult::failure(
				__( 'That file did not include a Client ID or Client Secret.', 'cannyforge-archive' )
			);
		}

		$fields = array();
		if ( '' !== $client_id ) {
			$fields['google_client_id'] = $client_id;
		}

		if ( '' !== $client_secret ) {
			$fields['google_client_secret'] = $client_secret;
		}

		return GoogleClientImportResult::success( $fields );
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
