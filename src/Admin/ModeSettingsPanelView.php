<?php
/**
 * Renders the mode-specific settings panels.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

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
	 * @param string         $google_refresh_url    Refresh action URL.
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
		string $google_refresh_url,
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
			$google_refresh_url,
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
	 * @param string         $google_refresh_url    Refresh action URL.
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
		string $google_refresh_url,
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
			$google_refresh_url,
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
	 * @param string         $refresh_url    Refresh action URL.
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
		string $refresh_url,
		string $notice,
		string $notice_type
	): void {
		echo '<div style="margin-top:1.5rem;padding:1rem;border:1px solid var(--cf-border);border-radius:16px;background:rgba(247,245,255,0.6);">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'Google Top Content (Search Console)', 'cannyforge-archive' ) . '</h3>';
		echo '<p class="description">';
		echo esc_html__( 'Ticket 404 foundation: save the OAuth client details here, then connect a Google account for read-only Search Console access. Page renders never call Google directly.', 'cannyforge-archive' );
		echo '</p>';
		$this->render_google_notice( $notice, $notice_type );
		$this->render_google_fields( $settings, $secret_saved, $status );
		$this->render_google_actions( $connect_url, $disconnect_url, $refresh_url );
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
	 * Render the Google connect/disconnect actions.
	 *
	 * @param string $connect_url    Connect action URL.
	 * @param string $disconnect_url Disconnect action URL.
	 * @param string $refresh_url    Refresh action URL.
	 * @return void
	 */
	private function render_google_actions( string $connect_url, string $disconnect_url, string $refresh_url ): void {
		wp_nonce_field( GoogleConnectionController::CONNECT_NONCE_ACTION, GoogleConnectionController::CONNECT_NONCE_FIELD );
		wp_nonce_field( GoogleConnectionController::DISCONNECT_NONCE_ACTION, GoogleConnectionController::DISCONNECT_NONCE_FIELD );
		wp_nonce_field( SearchConsoleRefreshController::REFRESH_NONCE_ACTION, SearchConsoleRefreshController::REFRESH_NONCE_FIELD );
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
			esc_url( $refresh_url ),
			esc_html__( 'Refresh now', 'cannyforge-archive' )
		);
		echo '</p>';
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
