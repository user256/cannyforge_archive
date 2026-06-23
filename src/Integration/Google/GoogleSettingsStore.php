<?php
/**
 * Dedicated option store for the archive plugin's Google configuration.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

/**
 * Persists the non-token Google settings outside the main archive option.
 *
 * The client secret is encrypted at rest; a blank secret on save preserves the
 * existing stored secret so the admin UI can leave the password field empty
 * without wiping the saved value.
 */
final class GoogleSettingsStore {
	/**
	 * Option key holding the Google configuration.
	 */
	public const OPTION_KEY = 'cannyforge_archive_google_settings';

	/**
	 * Secret cipher for the client secret.
	 *
	 * @var SecretCipher
	 */
	private SecretCipher $cipher;

	/**
	 * Get-option callable: fn(string $key, mixed $fallback): mixed.
	 *
	 * @var callable
	 */
	private $get_option;

	/**
	 * Set-option callable: fn(string $key, mixed $value): void.
	 *
	 * @var callable
	 */
	private $set_option;

	/**
	 * Construct the store.
	 *
	 * @param SecretCipher  $cipher     Secret cipher.
	 * @param callable|null $get_option Get-option accessor.
	 * @param callable|null $set_option Set-option accessor.
	 */
	public function __construct(
		?SecretCipher $cipher = null,
		?callable $get_option = null,
		?callable $set_option = null
	) {
		$this->cipher     = $cipher ?? new SecretCipher();
		$this->get_option = $get_option ?? static function ( string $key, $fallback ) {
			return function_exists( 'get_option' ) ? get_option( $key, $fallback ) : $fallback;
		};
		$this->set_option = $set_option ?? static function ( string $key, $value ): void {
			if ( function_exists( 'update_option' ) ) {
				update_option( $key, $value, false );
			}
		};
	}

	/**
	 * Load the current Google settings.
	 *
	 * @return GoogleSettings
	 */
	public function get(): GoogleSettings {
		$stored = ( $this->get_option )( self::OPTION_KEY, array() );
		$data   = is_array( $stored ) ? $stored : array();

		return GoogleSettings::from_array(
			array(
				'client_id'               => $data['client_id'] ?? '',
				'client_secret'           => $this->decrypt_secret( $data['client_secret'] ?? '' ),
				'search_console_site_url' => $data['search_console_site_url'] ?? '',
				'report_window_days'      => $data['report_window_days'] ?? 30,
				'ga4_property_id'         => $data['ga4_property_id'] ?? '',
			)
		);
	}

	/**
	 * Persist the given Google settings.
	 *
	 * A blank incoming client secret preserves the existing stored secret, so the
	 * admin UI can leave that field empty while still updating the non-secret
	 * settings.
	 *
	 * @param GoogleSettings $settings Settings to store.
	 * @return void
	 */
	public function save( GoogleSettings $settings ): void {
		$current         = ( $this->get_option )( self::OPTION_KEY, array() );
		$current         = is_array( $current ) ? $current : array();
		$encrypted       = '' !== $settings->client_secret()
			? $this->cipher->encrypt( $settings->client_secret() )
			: $this->stored_secret( $current );
		$stored_settings = array(
			'client_id'               => $settings->client_id(),
			'client_secret'           => $encrypted,
			'search_console_site_url' => $settings->search_console_site_url(),
			'report_window_days'      => $settings->report_window_days(),
			'ga4_property_id'         => $settings->ga4_property_id(),
		);

		( $this->set_option )( self::OPTION_KEY, $stored_settings );
	}

	/**
	 * Whether a client secret is already stored.
	 *
	 * @return bool
	 */
	public function has_client_secret(): bool {
		return $this->get()->has_client_secret();
	}

	/**
	 * Read the stored encrypted secret as-is from the raw option payload.
	 *
	 * @param array<string, mixed> $current Raw stored option payload.
	 * @return string
	 */
	private function stored_secret( array $current ): string {
		return isset( $current['client_secret'] ) && is_string( $current['client_secret'] )
			? $current['client_secret']
			: '';
	}

	/**
	 * Decrypt a stored client secret value, tolerating bad shapes safely.
	 *
	 * @param mixed $stored Stored raw value.
	 * @return string
	 */
	private function decrypt_secret( mixed $stored ): string {
		return is_scalar( $stored ) ? $this->cipher->decrypt( (string) $stored ) : '';
	}
}
