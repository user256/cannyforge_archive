<?php
/**
 * The full-page Google setup wizard screen.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4PropertyStore;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyStore;

/**
 * Routes, gates, and saves the stepped Google setup wizard (ticket 611 → 620).
 *
 * The wizard is a sub-view of the settings admin page, addressed with
 * `?cf_wizard=google&cf_step=<step>`, one screen per step with a progress
 * stepper — modelled on the appliance operator setup wizard rather than the
 * old single-dialog "wizard". Each step posts only its own fields; saves are
 * merged over the stored Google settings via
 * {@see GoogleSettingsStore::save_overlay()} so a step can never wipe fields
 * it did not render.
 */
final class GoogleWizardPage {
	/**
	 * Query arg flagging a wizard request on the settings page.
	 */
	public const QUERY_FLAG = 'cf_wizard';

	/**
	 * The {@see self::QUERY_FLAG} value for this wizard.
	 */
	public const FLAG_VALUE = 'google';

	/**
	 * Query arg carrying the requested step.
	 */
	public const QUERY_STEP = 'cf_step';

	/**
	 * Hidden form field the admin-post actions echo back so their redirect
	 * returns into the wizard at that step instead of the settings page.
	 */
	public const RETURN_STEP_FIELD = 'cf_wizard_step';

	/**
	 * Request arg carrying the chosen signal path (`sc`, `ga4`, or `sc_ga4`).
	 */
	public const SIGNAL_FIELD = 'cf_signal';

	/**
	 * Signal value: Search Console only.
	 */
	public const SIGNAL_SC = 'sc';

	/**
	 * Signal value: Analytics only.
	 */
	public const SIGNAL_GA4 = 'ga4';

	/**
	 * Signal value: Search Console with the GA4 fallback.
	 */
	public const SIGNAL_SC_GA4 = 'sc_ga4';

	/**
	 * Whether a signal path uses Search Console.
	 *
	 * @param string $signal Signal path.
	 * @return bool
	 */
	public static function signal_uses_search_console( string $signal ): bool {
		return in_array( $signal, array( self::SIGNAL_SC, self::SIGNAL_SC_GA4 ), true );
	}

	/**
	 * Whether a signal path uses Analytics.
	 *
	 * @param string $signal Signal path.
	 * @return bool
	 */
	public static function signal_uses_analytics( string $signal ): bool {
		return in_array( $signal, array( self::SIGNAL_GA4, self::SIGNAL_SC_GA4 ), true );
	}

	/**
	 * The form field name carrying the wizard-save nonce.
	 */
	public const NONCE_FIELD = 'cannyforge_archive_google_wizard_nonce';

	/**
	 * The wizard-save nonce action.
	 */
	public const NONCE_ACTION = 'cannyforge_archive_google_wizard_save';

	/**
	 * Step keys, in wizard order.
	 */
	public const STEP_SIGNAL      = 'signal';
	public const STEP_APP         = 'app';
	public const STEP_CREDENTIALS = 'credentials';
	public const STEP_CONNECT     = 'connect';
	public const STEP_PROPERTY    = 'property';
	public const STEP_DONE        = 'done';

	/**
	 * Capability required to run the wizard.
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Google settings store.
	 *
	 * @var GoogleSettingsStore
	 */
	private GoogleSettingsStore $settings;

	/**
	 * Google token store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $tokens;

	/**
	 * Cached Search Console properties.
	 *
	 * @var SearchConsolePropertyStore
	 */
	private SearchConsolePropertyStore $properties;

	/**
	 * Cached Search Console top-content IDs.
	 *
	 * @var SearchConsoleCacheStore
	 */
	private SearchConsoleCacheStore $search_cache;

	/**
	 * Cached GA4 top-content IDs.
	 *
	 * @var Ga4CacheStore
	 */
	private Ga4CacheStore $ga4_cache;

	/**
	 * Cached GA4 properties.
	 *
	 * @var Ga4PropertyStore
	 */
	private Ga4PropertyStore $ga4_properties;

	/**
	 * OAuth client JSON upload reader.
	 *
	 * @var GoogleClientJsonUploadReader
	 */
	private GoogleClientJsonUploadReader $json_upload;

	/**
	 * Wizard shell renderer.
	 *
	 * @var GoogleWizardView
	 */
	private GoogleWizardView $view;

	/**
	 * Construct the wizard screen.
	 *
	 * @param GoogleSettingsStore|null          $settings     Google settings store.
	 * @param GoogleTokenStore|null             $tokens       Google token store.
	 * @param SearchConsolePropertyStore|null   $properties   Property cache.
	 * @param SearchConsoleCacheStore|null      $search_cache Search Console cache store.
	 * @param Ga4CacheStore|null                $ga4_cache    GA4 cache store.
	 * @param GoogleClientJsonUploadReader|null $json_upload  Client JSON upload reader.
	 * @param GoogleWizardView|null             $view         Wizard shell renderer.
	 * @param Ga4PropertyStore|null             $ga4_properties Cached GA4 property cache.
	 */
	public function __construct(
		?GoogleSettingsStore $settings = null,
		?GoogleTokenStore $tokens = null,
		?SearchConsolePropertyStore $properties = null,
		?SearchConsoleCacheStore $search_cache = null,
		?Ga4CacheStore $ga4_cache = null,
		?GoogleClientJsonUploadReader $json_upload = null,
		?GoogleWizardView $view = null,
		?Ga4PropertyStore $ga4_properties = null
	) {
		$this->settings       = $settings ?? new GoogleSettingsStore();
		$this->tokens         = $tokens ?? new GoogleTokenStore();
		$this->properties     = $properties ?? new SearchConsolePropertyStore();
		$this->search_cache   = $search_cache ?? new SearchConsoleCacheStore();
		$this->ga4_cache      = $ga4_cache ?? new Ga4CacheStore();
		$this->json_upload    = $json_upload ?? new GoogleClientJsonUploadReader();
		$this->view           = $view ?? new GoogleWizardView();
		$this->ga4_properties = $ga4_properties ?? new Ga4PropertyStore();
	}

	/**
	 * Whether the current settings-page request addresses this wizard.
	 *
	 * @return bool
	 */
	public static function is_requested(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only routing flag.
		$raw = isset( $_GET[ self::QUERY_FLAG ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_FLAG ] ) ) : '';

		return self::FLAG_VALUE === $raw;
	}

	/**
	 * The wizard URL, optionally addressed to one step.
	 *
	 * @param string $step Step key, or '' for the state-derived default step.
	 * @return string
	 */
	public static function url( string $step = '' ): string {
		$args = array(
			'page'           => SettingsPage::PAGE_SLUG,
			self::QUERY_FLAG => self::FLAG_VALUE,
		);
		if ( GoogleWizardStepPlanner::is_step( $step ) ) {
			$args[ self::QUERY_STEP ] = $step;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Build a wizard URL carrying the selected signal.
	 *
	 * @param string $step   Wizard step.
	 * @param string $signal Selected signal path.
	 * @return string
	 */
	public static function url_with_signal( string $step, string $signal ): string {
		return add_query_arg(
			array( self::SIGNAL_FIELD => $signal ),
			self::url( $step )
		);
	}

	/**
	 * The wizard return step an admin-post action was submitted with, or ''.
	 *
	 * Read from the posted {@see self::RETURN_STEP_FIELD} hidden field. Only
	 * called by handlers that have already verified a nonce and capability.
	 *
	 * @return string
	 */
	public static function return_step_from_request(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read only by handlers that have verified their nonce.
		$step = isset( $_POST[ self::RETURN_STEP_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::RETURN_STEP_FIELD ] ) ) : '';

		return GoogleWizardStepPlanner::is_step( $step ) ? $step : '';
	}

	/**
	 * The URL an admin-post action should redirect back to: the wizard step
	 * it was submitted from, or the given settings-page fallback.
	 *
	 * @param string $fallback Fallback URL when no wizard step was posted.
	 * @return string
	 */
	public static function redirect_base_from_request( string $fallback ): string {
		$step = self::return_step_from_request();
		$base = '' !== $step ? self::url( $step ) : $fallback;
		// Keep the selected signal on admin-post redirects before its first
		// GA4 property ID has been saved.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller verifies its nonce before calling this helper.
		$signal = isset( $_POST[ self::SIGNAL_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::SIGNAL_FIELD ] ) ) : '';
		if ( in_array( $signal, array( self::SIGNAL_SC, self::SIGNAL_GA4, self::SIGNAL_SC_GA4 ), true ) ) {
			$base = add_query_arg( array( self::SIGNAL_FIELD => $signal ), $base );
		}

		return $base;
	}

	/**
	 * Handle a wizard save if present, then render the wizard page.
	 *
	 * Called from the settings page render after the capability check, so —
	 * like the settings save — a post-save redirect is not possible; the
	 * wizard re-renders at the posted target step with an inline notice.
	 *
	 * @param string $notice      One-shot notice text from an admin-post redirect.
	 * @param string $notice_type One-shot notice type.
	 * @return void
	 */
	public function render_page( string $notice, string $notice_type ): void {
		$saved    = $this->maybe_save();
		$settings = $this->settings->get();
		$status   = $this->connection_status();
		$signal   = $this->signal( $settings );
		$state    = $this->state( $settings, $status, $signal );
		$planner  = new GoogleWizardStepPlanner();

		$this->view->render(
			$planner->resolve_step( $state ),
			$planner->steps_meta( $state ),
			$settings,
			$status,
			$this->properties->get(),
			$this->ga4_properties->get(),
			$signal,
			$saved,
			$this->json_upload->error(),
			$notice,
			$notice_type,
			$this->tokens->analytics_scope_granted()
		);
	}

	/**
	 * Persist a wizard step submission when a valid one is present.
	 *
	 * @return bool True when Google settings were saved.
	 */
	private function maybe_save(): bool {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$input = wp_unslash( $_POST );
		$input = is_array( $input ) ? $input : array();
		$input = array_merge( $input, $this->json_upload->fields() );
		$this->settings->save_overlay( $input );
		$this->search_cache->clear();
		$this->ga4_cache->clear();

		return true;
	}

	/**
	 * The Google connection status, substituting the "needs re-authorising"
	 * pseudo-status (ticket 605) when the stored token cannot be read.
	 *
	 * @return string
	 */
	private function connection_status(): string {
		return $this->tokens->connection_needs_reauthorising()
			? GoogleTokenStore::STATUS_NEEDS_REAUTH
			: $this->tokens->status();
	}

	/**
	 * Derive the wizard's gating state from stored settings and connection.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @param string         $signal   Chosen signal path.
	 * @return array{has_credentials: bool, connected: bool, has_property: bool, has_ga4: bool}
	 */
	private function state( GoogleSettings $settings, string $status, string $signal ): array {
		return array(
			'has_credentials' => '' !== $settings->client_id() && $this->settings->has_client_secret(),
			'connected'       => GoogleTokenStore::STATUS_CONNECTED === $status,
			'has_property'    => self::signal_uses_analytics( $signal )
				? '' !== $settings->ga4_property_id()
				: '' !== $settings->search_console_site_url(),
			'has_ga4'         => '' !== $settings->ga4_property_id(),
		);
	}

	/**
	 * The chosen signal path: the request value when valid, otherwise derived
	 * from the stored property combination.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @return string
	 */
	private function signal( GoogleSettings $settings ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only routing value, carried between GET steps.
		$raw   = isset( $_REQUEST[ self::SIGNAL_FIELD ] ) ? sanitize_key( wp_unslash( $_REQUEST[ self::SIGNAL_FIELD ] ) ) : '';
		$valid = array( self::SIGNAL_SC, self::SIGNAL_GA4, self::SIGNAL_SC_GA4 );

		if ( in_array( $raw, $valid, true ) ) {
			return $raw;
		}

		if ( '' !== $settings->search_console_site_url() && '' !== $settings->ga4_property_id() ) {
			return self::SIGNAL_SC_GA4;
		}

		return '' !== $settings->ga4_property_id() ? self::SIGNAL_GA4 : self::SIGNAL_SC;
	}
}
