<?php
/**
 * Admin-post handlers for the Google OAuth connect flow.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4PropertyClient;
use CannyForge\Archive\Integration\Google\Ga4PropertyStore;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleOauthScopePolicy;
use CannyForge\Archive\Integration\Google\GoogleRevocationService;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyClient;
use CannyForge\Archive\Integration\Google\SearchConsolePropertyStore;

/**
 * Owns the Google connect / callback / disconnect admin-post flow.
 *
 * This is the WordPress-admin surface for ticket 404; the OAuth machinery and
 * secure persistence live in the Integration layer.
 */
final class GoogleConnectionController {
	/**
	 * Admin-post action: start connect flow.
	 */
	public const ACTION_CONNECT = 'cannyforge_archive_google_connect';

	/**
	 * Admin-post action: OAuth callback.
	 */
	public const ACTION_CALLBACK = 'cannyforge_archive_google_callback';

	/**
	 * Admin-post action: disconnect Google.
	 */
	public const ACTION_DISCONNECT = 'cannyforge_archive_google_disconnect';

	/**
	 * Nonce field for the connect action.
	 */
	public const CONNECT_NONCE_FIELD = 'cannyforge_archive_google_connect_nonce';

	/**
	 * Nonce action for the connect action.
	 */
	public const CONNECT_NONCE_ACTION = 'cannyforge_archive_google_connect';

	/**
	 * Nonce field for the disconnect action.
	 */
	public const DISCONNECT_NONCE_FIELD = 'cannyforge_archive_google_disconnect_nonce';

	/**
	 * Nonce action for the disconnect action.
	 */
	public const DISCONNECT_NONCE_ACTION = 'cannyforge_archive_google_disconnect';

	/**
	 * Query arg carrying a one-shot settings-page notice.
	 */
	public const NOTICE_KEY = 'cf_google_notice';

	/**
	 * Query arg carrying the notice type.
	 */
	public const NOTICE_TYPE_KEY = 'cf_google_notice_type';

	/**
	 * Query arg carrying the settings-page notice type value for success.
	 */
	public const NOTICE_SUCCESS = 'success';

	/**
	 * Query arg carrying the settings-page notice type value for error.
	 */
	public const NOTICE_ERROR = 'error';

	/**
	 * Google OAuth authorization endpoint.
	 */
	private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

	/**
	 * Capability required to manage the integration.
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
	 * Best-effort Google token revocation, shared with uninstall (ticket 606).
	 *
	 * @var GoogleRevocationService
	 */
	private GoogleRevocationService $revocation;

	/**
	 * Cached Search Console properties.
	 *
	 * @var SearchConsolePropertyStore
	 */
	private SearchConsolePropertyStore $property_store;

	/**
	 * GA4 property cache.
	 *
	 * @var Ga4PropertyStore
	 */
	private Ga4PropertyStore $ga4_property_store;

	/**
	 * Post-OAuth property-list completion service.
	 *
	 * @var GoogleConnectionCallbackService
	 */
	private GoogleConnectionCallbackService $callback_service;

	/**
	 * Construct the controller.
	 *
	 * @param GoogleSettingsStore              $settings     Google settings store.
	 * @param GoogleTokenStore                 $tokens       Google token store.
	 * @param SearchConsoleCacheStore          $search_cache Search Console cache store.
	 * @param Ga4CacheStore|null               $ga4_cache    GA4 cache store.
	 * @param GoogleRevocationService|null     $revocation   Token revocation service.
	 * @param SearchConsolePropertyClient|null $property_client Property list client.
	 * @param SearchConsolePropertyStore|null  $property_store  Property cache.
	 * @param Ga4PropertyClient|null           $ga4_property_client GA4 property client.
	 * @param Ga4PropertyStore|null            $ga4_property_store  GA4 property cache.
	 */
	public function __construct(
		GoogleSettingsStore $settings,
		GoogleTokenStore $tokens,
		SearchConsoleCacheStore $search_cache,
		?Ga4CacheStore $ga4_cache = null,
		?GoogleRevocationService $revocation = null,
		?SearchConsolePropertyClient $property_client = null,
		?SearchConsolePropertyStore $property_store = null,
		?Ga4PropertyClient $ga4_property_client = null,
		?Ga4PropertyStore $ga4_property_store = null
	) {
		$this->settings           = $settings;
		$this->tokens             = $tokens;
		$this->search_cache       = $search_cache;
		$this->ga4_cache          = $ga4_cache ?? new Ga4CacheStore();
		$this->revocation         = $revocation ?? new GoogleRevocationService( $tokens );
		$search_client            = $property_client ?? new SearchConsolePropertyClient( $this->oauth_client() );
		$this->property_store     = $property_store ?? new SearchConsolePropertyStore();
		$ga4_client               = $ga4_property_client ?? new Ga4PropertyClient( $this->oauth_client() );
		$this->ga4_property_store = $ga4_property_store ?? new Ga4PropertyStore();
		$this->callback_service   = new GoogleConnectionCallbackService( $tokens, $search_client, $this->property_store, $ga4_client, $this->ga4_property_store );
	}

	/**
	 * Register the admin-post handlers.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_CONNECT, array( $this, 'start_connect' ) );
		add_action( 'admin_post_' . self::ACTION_CALLBACK, array( $this, 'handle_callback' ) );
		add_action( 'admin_post_' . self::ACTION_DISCONNECT, array( $this, 'disconnect' ) );
	}

	/**
	 * Start the Google OAuth connect flow.
	 *
	 * @return void
	 */
	public function start_connect(): void {
		$this->require_capability();
		check_admin_referer( self::CONNECT_NONCE_ACTION, self::CONNECT_NONCE_FIELD );

		$settings = $this->settings->get();
		if ( '' === $settings->client_id() || '' === $settings->client_secret() ) {
			$this->redirect_to_settings(
				__( 'Save Google Client ID and Client Secret first, then try Connect again.', 'cannyforge-archive' ),
				self::NOTICE_ERROR
			);
		}

		$from_wizard            = '' !== GoogleWizardPage::return_step_from_request();
		$signal                 = $from_wizard ? GoogleWizardStepPlanner::signal_from_request() : GoogleWizardPage::SIGNAL_SC;
		$request_analytics      = $from_wizard
			? GoogleWizardPage::signal_uses_analytics( $signal )
			: '' !== $settings->ga4_property_id();
		$request_search_console = $from_wizard
			? GoogleWizardPage::signal_uses_search_console( $signal )
			: true;
		$state                  = ( new GoogleOauthStateStore() )->create( $from_wizard, $request_analytics, $request_search_console );

		$params = array(
			'client_id'              => $settings->client_id(),
			'redirect_uri'           => $this->callback_url(),
			'response_type'          => 'code',
			'scope'                  => GoogleOauthScopePolicy::scope_string( $settings, $request_analytics, $request_search_console ),
			'access_type'            => 'offline',
			'prompt'                 => 'consent',
			'state'                  => $state,
			'include_granted_scopes' => 'true',
		);

		wp_redirect( esc_url_raw( add_query_arg( $params, self::GOOGLE_AUTH_URL ) ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Google OAuth requires an external authorization redirect.
		exit;
	}

	/**
	 * Handle the OAuth callback from Google.
	 *
	 * @return void
	 */
	public function handle_callback(): void {
		$this->require_capability();
		$callback = GoogleOauthCallbackRequest::read();

		// The CSRF state transient is validated and consumed before anything
		// else touches token status (ticket 614): an attacker hitting this
		// endpoint directly with `?error=...` and no/invalid state must not be
		// able to flip the connection into an error state.
		$state_data  = ( new GoogleOauthStateStore() )->consume( $callback['state'] );
		$from_wizard = $state_data['wizard'];
		$signal      = GoogleWizardStepPlanner::signal_from_scope_flags( $state_data['analytics'], $state_data['search_console'] );
		$error_base  = $from_wizard
			? GoogleWizardPage::url_with_signal( GoogleWizardPage::STEP_CONNECT, $signal )
			: $this->settings_url();

		if ( '' !== $callback['error'] ) {
			$this->tokens->set_status( GoogleTokenStore::STATUS_ERROR );
			$this->redirect_with_notice( $callback['error'], self::NOTICE_ERROR, $error_base );
		}

		$this->assert_callback_has_code( $callback['code'], $error_base );
		$result = $this->callback_service->complete( $this->oauth_client(), $callback['code'], $this->callback_url(), $state_data );
		if ( ! $result['success'] ) {
			$this->redirect_with_notice( $result['message'], $result['notice_type'], $error_base );
		}

		$target = $from_wizard
			? GoogleWizardPage::url_with_signal( GoogleWizardPage::STEP_PROPERTY, $signal )
			: $this->settings_url();
		$this->redirect_with_notice( $result['message'], $result['notice_type'], $target );
	}

	/**
	 * Disconnect the stored Google connection.
	 *
	 * Makes a best-effort call to Google's revocation endpoint before local
	 * cleanup (ticket 614). Local state and caches are always cleared, even
	 * when the remote call fails or there is nothing to revoke, so disconnect
	 * remains idempotent and never leaves stale credentials behind locally.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$this->require_capability();
		check_admin_referer( self::DISCONNECT_NONCE_ACTION, self::DISCONNECT_NONCE_FIELD );

		$revoked = $this->revocation->revoke_and_clear();
		$this->search_cache->clear();
		$this->ga4_cache->clear();
		$this->property_store->clear();
		$this->ga4_property_store->clear();

		$this->redirect_to_settings(
			$revoked
				? __( 'Google disconnected.', 'cannyforge-archive' )
				: __( 'Google disconnected locally, but Google could not confirm the access grant was revoked. You can revoke it manually from your Google Account permissions.', 'cannyforge-archive' ),
			$revoked ? self::NOTICE_SUCCESS : self::NOTICE_ERROR
		);
	}

	/**
	 * The admin-post callback URL registered with Google.
	 *
	 * @return string
	 */
	public function callback_url(): string {
		return admin_url( 'admin-post.php?action=' . self::ACTION_CALLBACK );
	}

	/**
	 * The settings screen URL.
	 *
	 * @return string
	 */
	private function settings_url(): string {
		return admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG );
	}

	/**
	 * Redirect back to where the action was submitted from — the wizard step
	 * when the posted form carried one, otherwise the settings page — with a
	 * one-shot notice.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type.
	 * @return never
	 */
	private function redirect_to_settings( string $message, string $type ): never {
		$this->redirect_with_notice(
			$message,
			$type,
			GoogleWizardPage::redirect_base_from_request( $this->settings_url() )
		);
	}

	/**
	 * Redirect to a target URL with a one-shot notice.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type.
	 * @param string $target  Target URL.
	 * @return never
	 */
	private function redirect_with_notice( string $message, string $type, string $target ): never {
		wp_safe_redirect(
			add_query_arg(
				array(
					self::NOTICE_KEY      => rawurlencode( $message ),
					self::NOTICE_TYPE_KEY => $type,
				),
				$target
			)
		);
		exit;
	}

	/**
	 * Enforce the required capability for these handlers.
	 *
	 * @return void
	 */
	private function require_capability(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'cannyforge-archive' ) );
		}
	}

	/**
	 * Redirect with an error when the callback omitted the authorization code.
	 *
	 * Called only after {@see GoogleOauthStateStore::consume()} has already
	 * validated the CSRF state, so this is safe to let mutate token status.
	 *
	 * @param string $code       Callback code.
	 * @param string $error_base Redirect base for the error notice.
	 * @return void
	 */
	private function assert_callback_has_code( string $code, string $error_base ): void {
		if ( '' !== $code ) {
			return;
		}

		$this->tokens->set_status( GoogleTokenStore::STATUS_ERROR );
		$this->redirect_with_notice(
			__( 'Google callback did not include the expected code value.', 'cannyforge-archive' ),
			self::NOTICE_ERROR,
			$error_base
		);
	}

	/**
	 * A Google OAuth client from the stored archive Google settings.
	 *
	 * @return GoogleOauthClient
	 */
	private function oauth_client(): GoogleOauthClient {
		$settings = $this->settings->get();

		return new GoogleOauthClient(
			$this->tokens,
			$settings->client_id(),
			$settings->client_secret()
		);
	}
}
