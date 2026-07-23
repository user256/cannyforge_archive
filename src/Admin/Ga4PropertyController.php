<?php
/**
 * Admin-post handler for loading GA4 properties.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\Ga4PropertyClient;
use CannyForge\Archive\Integration\Google\Ga4PropertyStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/** Fetches and caches the connected account's GA4 properties. */
final class Ga4PropertyController {
	public const ACTION_REFRESH = 'cannyforge_archive_google_ga4_properties';
	public const NONCE_FIELD    = 'cannyforge_archive_google_ga4_properties_nonce';
	public const NONCE_ACTION   = 'cannyforge_archive_google_ga4_properties';

	private const CAPABILITY = 'manage_options';

	/**
	 * Google connection status store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $tokens;
	/**
	 * GA4 property client.
	 *
	 * @var Ga4PropertyClient
	 */
	private Ga4PropertyClient $client;
	/**
	 * GA4 property cache.
	 *
	 * @var Ga4PropertyStore
	 */
	private Ga4PropertyStore $properties;

	/**
	 * Construct the controller.
	 *
	 * @param GoogleTokenStore  $tokens     Google connection status store.
	 * @param Ga4PropertyClient $client     GA4 property client.
	 * @param Ga4PropertyStore  $properties GA4 property cache.
	 */
	public function __construct( GoogleTokenStore $tokens, Ga4PropertyClient $client, Ga4PropertyStore $properties ) {
		$this->tokens     = $tokens;
		$this->client     = $client;
		$this->properties = $properties;
	}

	/** Register the admin-post handler. */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_REFRESH, array( $this, 'refresh' ) );
	}

	/** Fetch and cache properties for the connected account. */
	public function refresh(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'cannyforge-archive' ) );
		}
		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		if ( GoogleTokenStore::STATUS_CONNECTED !== $this->tokens->status() ) {
			$this->redirect( __( 'Connect Google before loading GA4 properties.', 'cannyforge-archive' ), GoogleConnectionController::NOTICE_ERROR );
		}

		// Do not leave a previous account's property list looking current after a
		// failed refresh.
		$this->properties->clear();
		$properties = $this->client->list_properties();
		if ( array() === $properties ) {
			$this->redirect(
				'' !== $this->client->last_error() ? $this->client->last_error() : __( 'Google returned no GA4 properties for this account.', 'cannyforge-archive' ),
				GoogleConnectionController::NOTICE_ERROR
			);
		}

		$this->properties->save( $properties );
		$this->redirect(
			sprintf(
				/* translators: %d: number of properties. */
				_n( '%d GA4 property loaded.', '%d GA4 properties loaded.', count( $properties ), 'cannyforge-archive' ),
				count( $properties )
			),
			GoogleConnectionController::NOTICE_SUCCESS
		);
	}

	/**
	 * Redirect to the originating settings or wizard screen.
	 *
	 * @param string $message Notice text.
	 * @param string $type    Notice type.
	 * @return never
	 */
	private function redirect( string $message, string $type ): never {
		wp_safe_redirect(
			add_query_arg(
				array(
					GoogleConnectionController::NOTICE_KEY => rawurlencode( $message ),
					GoogleConnectionController::NOTICE_TYPE_KEY => $type,
				),
				GoogleWizardPage::redirect_base_from_request( admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG ) )
			)
		);
		exit;
	}
}
