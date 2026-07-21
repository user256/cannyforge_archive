<?php
/**
 * Renders the mode-specific settings panels.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Presentation-only renderer for the News/Blog mode panels.
 */
final class ModeSettingsPanelView {
	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Construct the mode-panel renderer.
	 *
	 * @param FormFieldView|null $fields Shared field renderer.
	 */
	public function __construct( ?FormFieldView $fields = null ) {
		$this->fields = $fields ?? new FormFieldView();
	}

	/**
	 * Render the mode-dependent settings panels.
	 *
	 * @param Settings       $settings              Current settings.
	 * @param GoogleSettings $google_settings       Current Google settings.
	 * @param string         $google_status         Current Google connection status.
	 * @param bool           $google_secret_saved   Whether a client secret is already stored.
	 * @param string         $google_connect_url    Connect action URL.
	 * @param string         $google_disconnect_url Disconnect action URL.
	 * @param string         $google_notice         One-shot Google notice text.
	 * @param string         $google_notice_type    One-shot Google notice type.
	 * @return void
	 */
	public function render(
		Settings $settings,
		GoogleSettings $google_settings,
		string $google_status,
		bool $google_secret_saved,
		string $google_connect_url,
		string $google_disconnect_url,
		string $google_notice,
		string $google_notice_type
	): void {
		$this->render_news_panel( $settings );
		$this->render_blog_panel(
			$settings,
			$google_settings,
			$google_status,
			$google_secret_saved,
			$google_connect_url,
			$google_disconnect_url,
			$google_notice,
			$google_notice_type
		);
	}

	/**
	 * Render the News mode controls.
	 *
	 * @param Settings $settings Current settings.
	 * @return void
	 */
	private function render_news_panel( Settings $settings ): void {
		echo '<div class="cf-panel-news" style="margin-top: 1rem; border-top: 1px solid var(--cf-border); padding-top: 1rem;">';
		echo '<h2>' . esc_html__( 'News Cycle Settings', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'A list of posts published in the last <insert newscycle settings>.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p><label>' . esc_html__( 'Include content published in the last (hours)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" step="1" name="news_window_hours" value="%d"></label></p>',
			absint( $settings->news_window_hours() )
		);
		echo '<p><label>' . esc_html__( 'When that window is empty, show the latest (posts)', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" max="500" step="1" name="news_fallback_count" value="%d"></label></p>',
			absint( $settings->news_fallback_count() )
		);
		echo '<p class="description">';
		echo esc_html__( 'Fallback so the archive is never blank when no post falls inside the recent window.', 'cannyforge-archive' );
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Render the Blog mode controls and Google panel.
	 *
	 * @param Settings       $settings              Current settings.
	 * @param GoogleSettings $google_settings       Current Google settings.
	 * @param string         $google_status         Current Google connection status.
	 * @param bool           $google_secret_saved   Whether a client secret is already stored.
	 * @param string         $google_connect_url    Connect action URL.
	 * @param string         $google_disconnect_url Disconnect action URL.
	 * @param string         $google_notice         One-shot Google notice text.
	 * @param string         $google_notice_type    One-shot Google notice type.
	 * @return void
	 */
	private function render_blog_panel(
		Settings $settings,
		GoogleSettings $google_settings,
		string $google_status,
		bool $google_secret_saved,
		string $google_connect_url,
		string $google_disconnect_url,
		string $google_notice,
		string $google_notice_type
	): void {
		echo '<div class="cf-panel-blog" style="margin-top: 1rem; border-top: 1px solid var(--cf-border); padding-top: 1rem;">';
		echo '<h2>' . esc_html__( 'Top Articles', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'A list of top articles manually set.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p><label>' . esc_html__( 'Maximum curated URLs', 'cannyforge-archive' ) . ' ';
		printf(
			'<input type="number" min="1" name="blog_max_urls" value="%d"></label></p>',
			absint( $settings->blog_max_urls() )
		);
		echo '<p><textarea name="blog_urls" rows="8" cols="50">';
		echo esc_textarea( implode( "\n", $settings->blog_urls() ) );
		echo '</textarea></p>';

		echo '<p><label>' . esc_html__( 'Import URLs from CSV', 'cannyforge-archive' ) . '<br>';
		echo '<input type="file" name="blog_urls_csv" accept=".csv,text/csv"></label></p>';
		$this->fields->checkbox(
			'blog_urls_csv_replace',
			__( 'Replace the list with the CSV (otherwise merge)', 'cannyforge-archive' ),
			false
		);
		echo '<p class="description">';
		echo esc_html__( 'The first URL-like value in each CSV row is imported.', 'cannyforge-archive' );
		echo '</p>';
		$this->render_google_panel(
			$google_settings,
			$google_status,
			$google_secret_saved,
			$google_connect_url,
			$google_disconnect_url,
			$google_notice,
			$google_notice_type
		);
		echo '</div>';
	}

	/**
	 * Render the Google connect/configuration controls for Blog mode.
	 *
	 * @param GoogleSettings $settings       Current Google settings.
	 * @param string         $status         Connection status.
	 * @param bool           $secret_saved   Whether a client secret is already stored.
	 * @param string         $connect_url    Connect action URL.
	 * @param string         $disconnect_url Disconnect action URL.
	 * @param string         $notice         One-shot notice text.
	 * @param string         $notice_type    Notice type.
	 * @return void
	 */
	private function render_google_panel(
		GoogleSettings $settings,
		string $status,
		bool $secret_saved,
		string $connect_url,
		string $disconnect_url,
		string $notice,
		string $notice_type
	): void {
		echo '<div class="cannyforge-google-wizard">';
		echo '<h3 class="cannyforge-google-wizard__title">' . esc_html__( 'Google Top Content', 'cannyforge-archive' ) . '</h3>';
		echo '<p class="description">';
		echo esc_html__( 'Use the guided setup to connect Google, choose your content signal, and refresh the archive cache without needing to understand the raw API fields first.', 'cannyforge-archive' );
		echo '</p>';
		$this->render_google_notice( $notice, $notice_type );
		$this->render_google_summary( $settings, $status );
		echo '<p class="cannyforge-google-wizard__launcher">';
		printf(
			'<button type="button" class="button button-primary" data-cf-google-wizard-open>%s</button>',
			esc_html__( 'Open Google setup wizard', 'cannyforge-archive' )
		);
		echo ' ';
		echo '<span class="description">' . esc_html__( 'You can reopen this any time to update credentials, reconnect, or refresh the cache.', 'cannyforge-archive' ) . '</span>';
		echo '</p>';
		$this->render_google_wizard_modal(
			$settings,
			$status,
			$secret_saved,
			$connect_url,
			$disconnect_url,
			$notice
		);
		echo '</div>';
	}

	/**
	 * Render a one-shot Google panel notice.
	 *
	 * @param string $notice      Notice text.
	 * @param string $notice_type Notice type.
	 * @return void
	 */
	private function render_google_notice( string $notice, string $notice_type ): void {
		if ( '' === $notice ) {
			return;
		}

		$class = GoogleConnectionController::NOTICE_SUCCESS === $notice_type ? 'notice-success' : 'notice-error';
		printf(
			'<div class="notice %1$s inline"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $notice )
		);
	}

	/**
	 * Render the Google configuration fields and status copy.
	 *
	 * @param GoogleSettings $settings     Current Google settings.
	 * @param bool           $secret_saved Whether a client secret is already stored.
	 * @param string         $status       Connection status.
	 * @return void
	 */
	private function render_google_fields( GoogleSettings $settings, bool $secret_saved, string $status ): void {
		$secret_placeholder = $secret_saved
			? esc_attr__( 'Saved. Leave blank to keep it.', 'cannyforge-archive' )
			: esc_attr__( 'Paste the client secret, then save settings.', 'cannyforge-archive' );

		echo '<p><label>' . esc_html__( 'Import OAuth client JSON (optional)', 'cannyforge-archive' ) . '<br>';
		echo '<input type="file" name="google_client_json" accept=".json,application/json"></label></p>';
		echo '<p class="description">';
		echo esc_html__( 'In Google Auth Platform > Clients, open your Web application client and use Download JSON. Upload that file here to import the Client ID and Client Secret automatically on save.', 'cannyforge-archive' );
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
		printf(
			'<p><label>%s <input type="text" name="google_search_console_site_url" value="%s" placeholder="%s" style="width:100%%;"></label></p>',
			esc_html__( 'Search Console Site URL', 'cannyforge-archive' ),
			esc_attr( $settings->search_console_site_url() ),
			esc_attr__( 'sc-domain:example.com or https://example.com/', 'cannyforge-archive' )
		);
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
			esc_html( ucfirst( $status ) )
		);

		echo '<p class="description">';
		echo esc_html__( 'The client secret is never rendered back into the form. Leave it blank on save to keep the stored secret unchanged.', 'cannyforge-archive' );
		echo '</p>';
		echo '<p class="description">';
		echo esc_html__( 'Save Settings after editing the Google fields. Use Connect only after the client details are saved.', 'cannyforge-archive' );
		echo '</p>';
	}

	/**
	 * Render the condensed Google setup summary shown on the main settings page.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @return void
	 */
	private function render_google_summary( GoogleSettings $settings, string $status ): void {
		$has_credentials    = '' !== $settings->client_id();
		$has_search_console = '' !== $settings->search_console_site_url();
		$has_ga4            = '' !== $settings->ga4_property_id();

		echo '<div class="cannyforge-google-wizard__summary">';
		$this->render_google_summary_item(
			__( 'Credentials', 'cannyforge-archive' ),
			$has_credentials ? __( 'Saved', 'cannyforge-archive' ) : __( 'Needed', 'cannyforge-archive' ),
			$has_credentials
		);
		$this->render_google_summary_item(
			__( 'Google account', 'cannyforge-archive' ),
			ucfirst( $status ),
			GoogleTokenStore::STATUS_CONNECTED === $status
		);
		$this->render_google_summary_item(
			__( 'Search Console property', 'cannyforge-archive' ),
			$has_search_console ? __( 'Ready', 'cannyforge-archive' ) : __( 'Needed', 'cannyforge-archive' ),
			$has_search_console
		);
		$this->render_google_summary_item(
			__( 'GA4 fallback', 'cannyforge-archive' ),
			$has_ga4 ? __( 'Enabled', 'cannyforge-archive' ) : __( 'Off', 'cannyforge-archive' ),
			$has_ga4
		);
		echo '</div>';
	}

	/**
	 * Render one status pill inside the Google summary strip.
	 *
	 * @param string $label   Summary item label.
	 * @param string $value   Summary item value.
	 * @param bool   $is_good Whether the item is in a ready/healthy state.
	 * @return void
	 */
	private function render_google_summary_item( string $label, string $value, bool $is_good ): void {
		printf(
			'<div class="cannyforge-google-wizard__summary-item"><span class="cannyforge-google-wizard__summary-label">%1$s</span><span class="cannyforge-google-wizard__summary-value %2$s">%3$s</span></div>',
			esc_html( $label ),
			$is_good ? 'is-good' : 'is-pending',
			esc_html( $value )
		);
	}

	/**
	 * Render the guided Google setup modal.
	 *
	 * @param GoogleSettings $settings       Current Google settings.
	 * @param string         $status         Connection status.
	 * @param bool           $secret_saved   Whether a client secret is already stored.
	 * @param string         $connect_url    Connect action URL.
	 * @param string         $disconnect_url Disconnect action URL.
	 * @param string         $notice         One-shot notice text.
	 * @return void
	 */
	private function render_google_wizard_modal(
		GoogleSettings $settings,
		string $status,
		bool $secret_saved,
		string $connect_url,
		string $disconnect_url,
		string $notice
	): void {
		$ga4_enabled = '' !== $settings->ga4_property_id();

		echo '<dialog class="cannyforge-modal cannyforge-modal--wide" data-cf-google-wizard-dialog data-cf-google-wizard-auto-open="' . esc_attr( '' !== $notice ? '1' : '0' ) . '" aria-labelledby="cf-google-wizard-title">';
		echo '<button type="button" class="cannyforge-modal__close" aria-label="' . esc_attr__( 'Close wizard', 'cannyforge-archive' ) . '" data-cf-google-wizard-close>&times;</button>';
		echo '<h2 id="cf-google-wizard-title">' . esc_html__( 'Google top-content setup wizard', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'This wizard walks through the whole setup in order: choose your signal, create the Google app, save credentials, connect the account, and refresh the cache.', 'cannyforge-archive' );
		echo '</p>';

		echo '<ol class="cannyforge-google-wizard__steps">';
		echo '<li>';
		echo '<strong>' . esc_html__( 'Choose your signal path', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'Search Console is the main source and is enough for most sites. Turn on GA4 only if you also want an analytics fallback when Search Console returns nothing.', 'cannyforge-archive' ) . '</p>';
		printf(
			'<label class="cannyforge-google-wizard__toggle"><input type="checkbox" value="1" %1$s data-cf-google-ga4-toggle> %2$s</label>',
			checked( $ga4_enabled, true, false ),
			esc_html__( 'Also use GA4 as a fallback signal', 'cannyforge-archive' )
		);
		echo '</li>';

		echo '<li>';
		echo '<strong>' . esc_html__( 'Set up Google Auth Platform', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'In Google Cloud, create or select a project, then open Google Auth Platform. If this is a new project and Google shows a Get started prompt, complete that setup first. After that, the main screen you usually need here is Branding.', 'cannyforge-archive' ) . '</p>';
		echo '<p>';
		echo '<a class="button button-secondary" href="https://console.cloud.google.com/auth/branding" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Branding', 'cannyforge-archive' ) . '</a> ';
		echo '<a class="button button-secondary" href="https://developers.google.com/workspace/guides/configure-oauth-consent" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Google setup guide', 'cannyforge-archive' ) . '</a>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'If the app is not yet published and Google treats it as External, add yourself as a test user during the setup flow before trying to connect.', 'cannyforge-archive' ) . '</p>';
		echo '</li>';

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

		echo '<li>';
		echo '<strong>' . esc_html__( 'Copy and add this Redirect URI', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'In the OAuth web client form, find Authorized redirect URIs, click + Add URI, and paste this exact URL before saving the client. It must match exactly, including `https`, trailing slash, and the full `admin-post.php?action=...` path.', 'cannyforge-archive' ) . '</p>';
		echo '<code class="cannyforge-wizard-code" data-cf-google-callback-url>' . esc_html( $this->google_callback_url() ) . '</code>';
		echo '<p><button type="button" class="button" data-cf-google-copy-callback>' . esc_html__( 'Copy Redirect URI', 'cannyforge-archive' ) . '</button></p>';
		echo '</li>';

		echo '<li>';
		echo '<strong>' . esc_html__( 'Prepare the properties you want to read', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'In Search Console, use the property selector in the top-left and choose + Add property if this site is not there yet. Use Domain property if you control DNS; otherwise use a URL-prefix property. The Google account you connect here must already have access to that property.', 'cannyforge-archive' ) . '</p>';
		echo '<p>';
		echo '<a class="button button-secondary" href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Search Console', 'cannyforge-archive' ) . '</a> ';
		echo '<a class="button button-secondary" href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open GA4 Admin', 'cannyforge-archive' ) . '</a>';
		echo '</p>';
		echo '<p class="description">' . esc_html__( 'For the Search Console field below, use either `sc-domain:example.com` for a Domain property or the full URL such as `https://example.com/` for a URL-prefix property.', 'cannyforge-archive' ) . '</p>';
		echo '</li>';

		echo '<li>';
		echo '<strong>' . esc_html__( 'Save the Google details', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'Either paste the Client ID and Client Secret from the Clients page, or download the OAuth client JSON from Google and import it here. Then enter the Search Console property you prepared above.', 'cannyforge-archive' ) . '</p>';
		$this->render_google_fields( $settings, $secret_saved, $status );
		echo '</li>';

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

		echo '<li>';
		echo '<strong>' . esc_html__( 'Connect Google and refresh the cache', 'cannyforge-archive' ) . '</strong>';
		echo '<p>' . esc_html__( 'After saving the details, connect the Google account that has Search Console access and GA4 access if you enabled the fallback. Then refresh Search Console and optionally GA4 to populate the archive cache.', 'cannyforge-archive' ) . '</p>';
		$this->render_google_actions( $connect_url, $disconnect_url );
		echo '</li>';
		echo '</ol>';

		echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__( 'Save Google details', 'cannyforge-archive' ) . '</button></p>';
		echo '</dialog>';
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
	 * @return void
	 */
	private function render_google_actions( string $connect_url, string $disconnect_url ): void {
		wp_nonce_field( GoogleConnectionController::CONNECT_NONCE_ACTION, GoogleConnectionController::CONNECT_NONCE_FIELD );
		wp_nonce_field( GoogleConnectionController::DISCONNECT_NONCE_ACTION, GoogleConnectionController::DISCONNECT_NONCE_FIELD );
		wp_nonce_field( SearchConsoleRefreshController::REFRESH_NONCE_ACTION, SearchConsoleRefreshController::REFRESH_NONCE_FIELD );
		wp_nonce_field( Ga4RefreshController::REFRESH_NONCE_ACTION, Ga4RefreshController::REFRESH_NONCE_FIELD );
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
			default => '#475467',
		};
	}
}
