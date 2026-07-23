<?php
/**
 * Admin-post handler for loading Search Console properties.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyClient;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyStore;

/**
 * Fetches the connected account's Search Console properties on demand.
 */
final class SearchConsolePropertyController {
	/**
	 * Admin-post action.
	 */
	public const ACTION_REFRESH = 'cannyforge_archive_google_properties';

	/**
	 * Nonce field.
	 */
	public const NONCE_FIELD = 'cannyforge_archive_google_properties_nonce';

	/**
	 * Nonce action.
	 */
	public const NONCE_ACTION = 'cannyforge_archive_google_properties';

	/**
	 * Capability required to load properties.
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Google token store.
	 *
	 * @var GoogleTokenStore
	 */
	private GoogleTokenStore $tokens;

	/**
	 * Property client.
	 *
	 * @var SearchConsolePropertyClient
	 */
	private SearchConsolePropertyClient $client;

	/**
	 * Property cache.
	 *
	 * @var SearchConsolePropertyStore
	 */
	private SearchConsolePropertyStore $properties;

	/**
	 * Construct the controller.
	 *
	 * @param GoogleTokenStore            $tokens     Token store.
	 * @param SearchConsolePropertyClient $client     Property client.
	 * @param SearchConsolePropertyStore  $properties Property cache.
	 */
	public function __construct(
		GoogleTokenStore $tokens,
		SearchConsolePropertyClient $client,
		SearchConsolePropertyStore $properties
	) {
		$this->tokens     = $tokens;
		$this->client     = $client;
		$this->properties = $properties;
	}

	/**
	 * Register the admin-post handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_REFRESH, array( $this, 'refresh' ) );
	}

	/**
	 * Fetch and cache properties for the connected account.
	 *
	 * @return void
	 */
	public function refresh(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'cannyforge-archive' ) );
		}
		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		if ( GoogleTokenStore::STATUS_CONNECTED !== $this->tokens->status() ) {
			$this->redirect( __( 'Connect Google before loading Search Console properties.', 'cannyforge-archive' ), GoogleConnectionController::NOTICE_ERROR );
		}

		$properties = $this->client->list_properties();
		if ( array() === $properties ) {
			$this->redirect(
				'' !== $this->client->last_error()
					? $this->client->last_error()
					: __( 'Google returned no Search Console properties for this account.', 'cannyforge-archive' ),
				GoogleConnectionController::NOTICE_ERROR
			);
		}

		$this->properties->save( $properties );
		$this->redirect(
			sprintf(
				/* translators: %d: number of properties. */
				_n( '%d Search Console property loaded.', '%d Search Console properties loaded.', count( $properties ), 'cannyforge-archive' ),
				count( $properties )
			),
			GoogleConnectionController::NOTICE_SUCCESS
		);
	}

	/**
	 * Redirect back to where the action was submitted from — the wizard step
	 * when the posted form carried one, otherwise the settings page.
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
				GoogleWizardPage::redirect_base_from_request(
					admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG )
				)
			)
		);
		exit;
	}
}
