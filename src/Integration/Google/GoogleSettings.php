<?php
/**
 * Non-token Google integration settings.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable snapshot of the archive plugin's Google configuration.
 *
 * Secrets and tokens are stored outside the main archive settings option; this
 * object carries only the Google integration configuration needed by the admin
 * UI and OAuth client.
 */
final class GoogleSettings {
	/**
	 * Smallest permitted report window in days.
	 */
	private const MIN_REPORT_WINDOW_DAYS = 1;

	/**
	 * Largest permitted report window in days.
	 */
	private const MAX_REPORT_WINDOW_DAYS = 365;

	/**
	 * OAuth client ID.
	 *
	 * @var string
	 */
	private string $client_id;

	/**
	 * OAuth client secret (decrypted in memory only).
	 *
	 * @var string
	 */
	private string $client_secret;

	/**
	 * Search Console site URL / property identifier.
	 *
	 * @var string
	 */
	private string $search_console_site_url;

	/**
	 * Report window in days.
	 *
	 * @var int
	 */
	private int $report_window_days;

	/**
	 * GA4 property ID (numeric Analytics property identifier, without the
	 * `properties/` prefix). Empty when GA4 sourcing is not configured.
	 *
	 * @var string
	 */
	private string $ga4_property_id;

	/**
	 * Construct a settings snapshot.
	 *
	 * @param string $client_id               OAuth client ID.
	 * @param string $client_secret           OAuth client secret.
	 * @param string $search_console_site_url Search Console site URL.
	 * @param int    $report_window_days      Report window in days.
	 * @param string $ga4_property_id         GA4 property ID.
	 */
	public function __construct(
		string $client_id = '',
		string $client_secret = '',
		string $search_console_site_url = '',
		int $report_window_days = 30,
		string $ga4_property_id = ''
	) {
		$this->client_id               = trim( $client_id );
		$this->client_secret           = trim( $client_secret );
		$this->search_console_site_url = trim( $search_console_site_url );
		$this->report_window_days      = min(
			self::MAX_REPORT_WINDOW_DAYS,
			max( self::MIN_REPORT_WINDOW_DAYS, $report_window_days )
		);
		$this->ga4_property_id         = $this->clean_property_id( $ga4_property_id );
	}

	/**
	 * The OAuth client ID.
	 *
	 * @return string
	 */
	public function client_id(): string {
		return $this->client_id;
	}

	/**
	 * The OAuth client secret (decrypted in memory).
	 *
	 * @return string
	 */
	public function client_secret(): string {
		return $this->client_secret;
	}

	/**
	 * Whether a client secret is present.
	 *
	 * @return bool
	 */
	public function has_client_secret(): bool {
		return '' !== $this->client_secret;
	}

	/**
	 * The Search Console site URL / property identifier.
	 *
	 * @return string
	 */
	public function search_console_site_url(): string {
		return $this->search_console_site_url;
	}

	/**
	 * The report window in days.
	 *
	 * @return int
	 */
	public function report_window_days(): int {
		return $this->report_window_days;
	}

	/**
	 * The GA4 property ID (digits only), or '' when not configured.
	 *
	 * @return string
	 */
	public function ga4_property_id(): string {
		return $this->ga4_property_id;
	}

	/**
	 * Build from a raw associative array, coercing every value safely.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			self::to_string( $data['google_client_id'] ?? $data['client_id'] ?? null ),
			self::to_string( $data['google_client_secret'] ?? $data['client_secret'] ?? null ),
			self::to_string( $data['google_search_console_site_url'] ?? $data['search_console_site_url'] ?? null ),
			self::to_int( $data['google_report_window_days'] ?? $data['report_window_days'] ?? null, 30 ),
			self::to_string( $data['google_ga4_property_id'] ?? $data['ga4_property_id'] ?? null )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'client_id'               => $this->client_id,
			'client_secret'           => $this->client_secret,
			'search_console_site_url' => $this->search_console_site_url,
			'report_window_days'      => $this->report_window_days,
			'ga4_property_id'         => $this->ga4_property_id,
		);
	}

	/**
	 * Coerce a raw value to a trimmed string, defaulting to empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function to_string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Coerce a raw value to an int, defaulting on non-numeric input.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $fallback Fallback value.
	 * @return int
	 */
	private static function to_int( mixed $value, int $fallback ): int {
		return is_numeric( $value ) ? (int) $value : $fallback;
	}

	/**
	 * Normalise a GA4 property ID to digits only.
	 *
	 * Accepts a bare numeric ID or a `properties/123456` form and keeps only the
	 * numeric portion; anything else becomes ''.
	 *
	 * @param string $value Raw property ID.
	 * @return string
	 */
	private function clean_property_id( string $value ): string {
		$digits = preg_replace( '/\D+/', '', trim( $value ) );

		return is_string( $digits ) ? $digits : '';
	}
}
