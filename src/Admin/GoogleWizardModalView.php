<?php
/**
 * Renders the guided Google setup wizard modal.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleOauthScopePolicy;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Presentation-only renderer for the step-by-step Google setup wizard.
 *
 * Split out of ModeSettingsPanelView (ticket 611) to keep both classes
 * under the PHPMD length budget; this class owns only the wizard dialog,
 * its credential fields, and the connect/refresh actions.
 */
final class GoogleWizardModalView {
	/**
	 * Render the guided Google setup modal.
	 *
	 * @param GoogleSettings                                          $settings       Current Google settings.
	 * @param string                                                  $status         Connection status.
	 * @param bool                                                    $secret_saved   Whether a client secret is already stored.
	 * @param string                                                  $connect_url    Connect action URL.
	 * @param string                                                  $disconnect_url Disconnect action URL.
	 * @param string                                                  $notice         One-shot notice text.
	 * @param array<int, array{site_url: string, permission: string}> $properties Cached properties available to the connected account.
	 * @param string                                                  $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	public function render(
		GoogleSettings $settings,
		string $status,
		bool $secret_saved,
		string $connect_url,
		string $disconnect_url,
		string $notice,
		array $properties = array(),
		string $property_refresh_url = ''
	): void {
		$ga4_enabled = '' !== $settings->ga4_property_id();

		echo '<dialog class="cannyforge-modal cannyforge-modal--wide" data-cf-google-wizard-dialog data-cf-google-wizard-auto-open="' . esc_attr( '' !== $notice ? '1' : '0' ) . '" aria-labelledby="cf-google-wizard-title">';
		echo '<button type="button" class="cannyforge-modal__close" aria-label="' . esc_attr__( 'Close wizard', 'cannyforge-archive' ) . '" data-cf-google-wizard-close>&times;</button>';
		echo '<h2 id="cf-google-wizard-title">' . esc_html__( 'Google top-content setup wizard', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'This wizard walks through the whole setup in order: choose your signal, create the Google app, save credentials, connect the account, and refresh the cache.', 'cannyforge-archive' );
		echo '</p>';
		( new GoogleWizardProgressView() )->render( $settings, $status, $secret_saved, $properties );

		echo '<ol class="cannyforge-google-wizard__steps">';
		$this->render_step_signal( $ga4_enabled );
		$this->render_step_auth_platform();
		$this->render_step_oauth_client();
		$this->render_step_redirect_uri();
		$this->render_step_properties();
		$this->render_step_save_details( $settings, $secret_saved, $status, $properties, $property_refresh_url );
		$this->render_step_ga4_optional( $settings, $ga4_enabled );
		$this->render_step_connect( $settings, $connect_url, $disconnect_url, $property_refresh_url );
		echo '</ol>';

		echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__( 'Save Google details', 'cannyforge-archive' ) . '</button></p>';
		echo '</dialog>';
	}

	/**
	 * Render wizard step 1: choose the signal path.
	 *
	 * @param bool $ga4_enabled Whether the GA4 fallback is currently enabled.
	 * @return void
	 */
	private function render_step_signal( bool $ga4_enabled ): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Choose your signal path', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'Search Console is the main source and is enough for most sites. Turn on GA4 only if you also want an analytics fallback when Search Console returns nothing.', 'cannyforge-archive' ) . '</p>';
		printf(
			'<label class="cannyforge-google-wizard__toggle"><input type="checkbox" value="1" %1$s data-cf-google-ga4-toggle> %2$s</label>',
			checked( $ga4_enabled, true, false ),
			esc_html__( 'Also use GA4 as a fallback signal', 'cannyforge-archive' )
		);
		echo '</li>';
	}

	/**
	 * Render wizard step 2: set up Google Auth Platform.
	 *
	 * @return void
	 */
	private function render_step_auth_platform(): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Set up Google Auth Platform', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'In Google Cloud, create or select a project, then open Google Auth Platform. If this is a new project and Google shows a Get started prompt, complete that setup first. After that, the main screen you usually need here is Branding.', 'cannyforge-archive' ) . '</p>';
		echo '<p>';
		echo '<a class="button button-secondary" href="https://console.cloud.google.com/auth/branding" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Branding', 'cannyforge-archive' ) . '</a> ';
		echo '<a class="button button-secondary" href="https://developers.google.com/workspace/guides/configure-oauth-consent" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Google setup guide', 'cannyforge-archive' ) . '</a>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'If the app is not yet published and Google treats it as External, add yourself as a test user during the setup flow before trying to connect.', 'cannyforge-archive' ) . '</p>';
		echo '</li>';
	}

	/**
	 * Render wizard step 3: create the OAuth web client.
	 *
	 * @return void
	 */
	private function render_step_oauth_client(): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Create the OAuth web client', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'Open Google Auth Platform > Clients, click Create client, and choose Web application. Give it a clear name such as CannyForge Archive Generator. During client creation, add the Redirect URI from the next step into Authorized redirect URIs. Authorized JavaScript origins are not required for this plugin.', 'cannyforge-archive' ) . '</p>';
		echo '<p>';
		echo '<a class="button button-secondary" href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Clients', 'cannyforge-archive' ) . '</a> ';
		echo '<a class="button button-secondary" href="https://console.cloud.google.com/apis/library/searchconsole.googleapis.com" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Enable Search Console API', 'cannyforge-archive' ) . '</a> ';
		echo '<a class="button button-secondary" href="https://console.cloud.google.com/apis/library/analyticsdata.googleapis.com" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Enable Analytics Data API', 'cannyforge-archive' ) . '</a>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'Search Console API is required. Enable Analytics Data API only if you plan to use the GA4 fallback. In the client form, scroll to Authorized redirect URIs, click + Add URI, paste the exact callback URL shown below, then click Create.', 'cannyforge-archive' ) . '</p>';
		echo '</li>';
	}

	/**
	 * Render wizard step 4: copy the redirect URI.
	 *
	 * @return void
	 */
	private function render_step_redirect_uri(): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Copy and add this Redirect URI', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'In the OAuth web client form, find Authorized redirect URIs, click + Add URI, and paste this exact URL before saving the client. It must match exactly, including `https`, trailing slash, and the full `admin-post.php?action=...` path.', 'cannyforge-archive' ) . '</p>';
		echo '<code class="cannyforge-wizard-code" data-cf-google-callback-url>' . esc_html( $this->google_callback_url() ) . '</code>';
		echo '<p><button type="button" class="button" data-cf-google-copy-callback>' . esc_html__( 'Copy Redirect URI', 'cannyforge-archive' ) . '</button></p>';
		echo '</li>';
	}

	/**
	 * Render wizard step 5: prepare the properties to read.
	 *
	 * @return void
	 */
	private function render_step_properties(): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Make sure the property is available', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'The Google account you connect must already have access to the Search Console property. If this site is not listed in Search Console, add it there first; the plugin will load the available properties for you after connection.', 'cannyforge-archive' ) . '</p>';
		echo '<p>';
		echo '<a class="button button-secondary" href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Search Console', 'cannyforge-archive' ) . '</a> ';
		echo '<a class="button button-secondary" href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open GA4 Admin', 'cannyforge-archive' ) . '</a>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'You will select the property from the connected account below; there is no property identifier to copy manually.', 'cannyforge-archive' ) . '</p>';
		echo '</li>';
	}

	/**
	 * Render wizard step 6: save the Google credential fields.
	 *
	 * @param GoogleSettings                                          $settings     Current Google settings.
	 * @param bool                                                    $secret_saved Whether a client secret is already stored.
	 * @param string                                                  $status       Connection status.
	 * @param array<int, array{site_url: string, permission: string}> $properties Cached properties available to the connected account.
	 * @param string                                                  $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	private function render_step_save_details( GoogleSettings $settings, bool $secret_saved, string $status, array $properties, string $property_refresh_url ): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Add your Google credentials', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'Either paste the Client ID and Client Secret from the Clients page, or download the OAuth client JSON from Google and import it here. After connecting, choose the Search Console property from the account list.', 'cannyforge-archive' ) . '</p>';
		$this->render_google_fields( $settings, $secret_saved, $status, $properties, $property_refresh_url );
		echo '</li>';
	}

	/**
	 * Render wizard step 7: the optional GA4 fallback property field.
	 *
	 * @param GoogleSettings $settings    Current Google settings.
	 * @param bool           $ga4_enabled Whether the GA4 fallback is currently enabled.
	 * @return void
	 */
	private function render_step_ga4_optional( GoogleSettings $settings, bool $ga4_enabled ): void {
		echo '<li data-cf-google-ga4-fields' . ( $ga4_enabled ? '' : ' hidden' ) . '>';
		echo '<strong>' . esc_html__( 'Optional: add the GA4 fallback property', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'Leave this blank if Search Console is enough. If you enable GA4, enter the numeric GA4 Property ID, not the Measurement ID that starts with `G-`.', 'cannyforge-archive' ) . '</p>';
		printf(
			'<p><label>%s <input type="text" name="google_ga4_property_id" value="%s" placeholder="%s" style="width:100%%;"%s></label></p>',
			esc_html__( 'GA4 Property ID', 'cannyforge-archive' ),
			esc_attr( $settings->ga4_property_id() ),
			esc_attr__( 'numeric ID, e.g. 123456789', 'cannyforge-archive' ),
			$ga4_enabled ? '' : ' disabled'
		);
		echo '<p class="description">' . esc_html__( 'Open GA4, go to Admin, select the property, then open Property Settings and copy the numeric Property ID. Do not use the Measurement ID and do not include the `properties/` prefix.', 'cannyforge-archive' ) . '</p>';
		echo '</li>';
	}

	/**
	 * Render wizard step 8: connect and refresh actions.
	 *
	 * @param GoogleSettings $settings       Current Google settings.
	 * @param string         $connect_url    Connect action URL.
	 * @param string         $disconnect_url     Disconnect action URL.
	 * @param string         $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	private function render_step_connect( GoogleSettings $settings, string $connect_url, string $disconnect_url, string $property_refresh_url ): void {
		echo '<li>';
		echo '<strong>' . esc_html__( 'Connect Google and refresh the cache', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'After saving the details, connect the Google account that has Search Console access and GA4 access if you enabled the fallback. The available Search Console properties will load automatically; choose one from the dropdown, or use Load Search Console properties to refresh the list.', 'cannyforge-archive' ) . '</p>';
		$this->render_google_consent_copy( $settings );
		$this->render_google_actions( $connect_url, $disconnect_url, $property_refresh_url );
		echo '</li>';
	}

	/**
	 * Render the scopes-to-be-requested consent copy shown before Connect.
	 *
	 * This renders exactly the scope set returned by the central OAuth policy,
	 * so the visible consent copy cannot omit a scope the redirect requests.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @return void
	 */
	private function render_google_consent_copy( GoogleSettings $settings ): void {
		$labels       = array(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE => __( 'Search Console (read-only)', 'cannyforge-archive' ),
			GoogleOauthScopePolicy::SCOPE_ANALYTICS      => __( 'Google Analytics 4 (read-only)', 'cannyforge-archive' ),
		);
		$scope_labels = array_map(
			static fn ( string $scope ): string => $labels[ $scope ] ?? $scope,
			GoogleOauthScopePolicy::scopes( $settings )
		);

		echo '<p class="description" data-cf-google-consent-copy>';
		printf(
		/* translators: %s: comma-separated list of read-only Google scopes, e.g. "Search Console (read-only), Google Analytics 4 (read-only)". */
			esc_html__( 'Connecting will ask Google to grant read-only access to: %s.', 'cannyforge-archive' ),
			esc_html( implode( ', ', $scope_labels ) )
		);
		echo '</p>';
	}

	/**
	 * Render the Google configuration fields and status copy.
	 *
	 * @param GoogleSettings                                          $settings     Current Google settings.
	 * @param bool                                                    $secret_saved Whether a client secret is already stored.
	 * @param string                                                  $status       Connection status.
	 * @param array<int, array{site_url: string, permission: string}> $properties Cached properties available to the connected account.
	 * @param string                                                  $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	private function render_google_fields( GoogleSettings $settings, bool $secret_saved, string $status, array $properties, string $property_refresh_url ): void {
		$secret_placeholder = $secret_saved
		? esc_attr__( 'Saved. Leave blank to keep it.', 'cannyforge-archive' )
		: esc_attr__( 'Paste the client secret, then save settings.', 'cannyforge-archive' );
		echo '<p><label>' . esc_html__( 'Import OAuth client JSON (optional)', 'cannyforge-archive' ) . '<br>';
		echo '<input type="file" name="google_client_json" accept=".json,application/json"></label></p>';
		echo '<p class="description">';
		echo esc_html__( 'In Google Auth Platform > Clients, open your Web application client and use Download JSON. Upload that file and save these details; the Client ID and Secret will be imported securely on the server.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p class="cannyforge-google-wizard__inline-action">';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Save credentials and continue', 'cannyforge-archive' ) . '</button>';
		echo '</p>';
		printf(
			'<p><label>%s <input type="text" name="google_client_id" value="%s" autocomplete="off" style="width:100%%;"></label></p>',
			esc_html__( 'Google Client ID', 'cannyforge-archive' ),
			esc_attr( $settings->client_id() )
		);
		printf(
			'<p><label>%s <input type="password" name="google_client_secret" value="" placeholder="%s" autocomplete="new-password" style="width:100%%;"></label></p>',
			esc_html__( 'Google Client Secret', 'cannyforge-archive' ),
			esc_attr( $secret_placeholder )
		);
		( new GooglePropertySelectorView() )->render( $settings, $properties, $property_refresh_url );
		printf(
			'<p><label>%s <input type="number" min="1" max="365" step="1" name="google_report_window_days" value="%d"></label></p>',
			esc_html__( 'Report window (days)', 'cannyforge-archive' ),
			absint( $settings->report_window_days() )
		);
		printf(
			'<p><strong>%s</strong> <span style="display:inline-block;padding:0.2rem 0.55rem;border-radius:999px;background:%s;color:%s;">%s</span></p>',
			esc_html__( 'Connection status:', 'cannyforge-archive' ),
			esc_attr( $this->google_status_background( $status ) ),
			esc_attr( $this->google_status_color( $status ) ),
			esc_html( GoogleTokenStore::status_label( $status ) )
		);

		if ( GoogleTokenStore::STATUS_NEEDS_REAUTH === $status ) {
			echo '<p class="description">';
			echo esc_html__( "Connection needs re-authorising — the site's security keys may have changed, so the stored connection can no longer be read. Click Connect Google again to restore it; nothing else needs to change.", 'cannyforge-archive' );
			echo '</p>';
		}

		echo '<p class="description">';
		echo esc_html__( 'The client secret is never rendered back into the form. Leave it blank on save to keep the stored secret unchanged.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p class="description">';
		echo esc_html__( 'Save Settings after editing the Google fields. Use Connect only after the client details are saved.', 'cannyforge-archive' );
		echo '</p>';
	}

	/**
	 * Render the Google connect/disconnect actions.
	 *
	 * The Connect/Disconnect URLs are passed in by the page controller; the
	 * per-source refresh URLs are derived here from the admin-post action
	 * constants so the panel can offer one refresh button per Google signal
	 * without expanding the render call chain.
	 *
	 * @param string $connect_url    Connect action URL.
	 * @param string $disconnect_url Disconnect action URL.
	 * @param string $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	private function render_google_actions( string $connect_url, string $disconnect_url, string $property_refresh_url = '' ): void {
		wp_nonce_field( GoogleConnectionController::CONNECT_NONCE_ACTION, GoogleConnectionController::CONNECT_NONCE_FIELD );
		wp_nonce_field( GoogleConnectionController::DISCONNECT_NONCE_ACTION, GoogleConnectionController::DISCONNECT_NONCE_FIELD );
		wp_nonce_field( SearchConsoleRefreshController::REFRESH_NONCE_ACTION, SearchConsoleRefreshController::REFRESH_NONCE_FIELD );
		wp_nonce_field( Ga4RefreshController::REFRESH_NONCE_ACTION, Ga4RefreshController::REFRESH_NONCE_FIELD );
		if ( '' !== $property_refresh_url ) {
			wp_nonce_field( SearchConsolePropertyController::NONCE_ACTION, SearchConsolePropertyController::NONCE_FIELD );
		}
		echo '<p style="display:flex;gap:0.75rem;flex-wrap:wrap;">';
		printf(
			'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
			esc_url( $connect_url ),
			esc_html__( 'Connect Google', 'cannyforge-archive' )
		);
		printf(
			'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
			esc_url( $disconnect_url ),
			esc_html__( 'Disconnect', 'cannyforge-archive' )
		);
		printf(
			'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
			esc_url( $this->refresh_action_url( SearchConsoleRefreshController::ACTION_REFRESH ) ),
			esc_html__( 'Refresh Search Console', 'cannyforge-archive' )
		);
		if ( '' !== $property_refresh_url ) {
			printf(
				'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
				esc_url( $property_refresh_url ),
				esc_html__( 'Load Search Console properties', 'cannyforge-archive' )
			);
		}
		printf(
			'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
			esc_url( $this->refresh_action_url( Ga4RefreshController::ACTION_REFRESH ) ),
			esc_html__( 'Refresh GA4', 'cannyforge-archive' )
		);
		echo '</p>';
	}

	/**
	 * The admin-post callback URL that must be registered in Google Cloud.
	 *
	 * @return string
	 */
	private function google_callback_url(): string {
		return admin_url( 'admin-post.php?action=' . GoogleConnectionController::ACTION_CALLBACK );
	}

	/**
	 * Build an admin-post URL for a refresh action.
	 *
	 * @param string $action Admin-post action name.
	 * @return string
	 */
	private function refresh_action_url( string $action ): string {
		return admin_url( 'admin-post.php?action=' . $action );
	}

	/**
	 * Background color for the Google connection status pill.
	 *
	 * @param string $status Connection status.
	 * @return string
	 */
	private function google_status_background( string $status ): string {
		return match ( $status ) {
			GoogleTokenStore::STATUS_CONNECTED => '#e8fff4',
			GoogleTokenStore::STATUS_EXPIRED => '#fff4e5',
			GoogleTokenStore::STATUS_ERROR => '#ffeaea',
			GoogleTokenStore::STATUS_NEEDS_REAUTH => '#fff4e5',
			default => '#f0f2f5',
		};
	}

	/**
	 * Text color for the Google connection status pill.
	 *
	 * @param string $status Connection status.
	 * @return string
	 */
	private function google_status_color( string $status ): string {
		return match ( $status ) {
			GoogleTokenStore::STATUS_CONNECTED => '#0f7a43',
			GoogleTokenStore::STATUS_EXPIRED => '#a05a00',
			GoogleTokenStore::STATUS_ERROR => '#b42318',
			GoogleTokenStore::STATUS_NEEDS_REAUTH => '#a05a00',
			default => '#475467',
		};
	}
}
