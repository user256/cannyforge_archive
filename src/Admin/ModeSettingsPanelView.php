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
 *
 * The guided Google setup modal lives in GoogleWizardModalView (split out in
 * ticket 611 to keep both classes under the PHPMD length budget); this class
 * owns the mode panels and the condensed Google summary/launcher.
 */
final class ModeSettingsPanelView {
	/**
	 * Shared field renderer.
	 *
	 * @var FormFieldView
	 */
	private FormFieldView $fields;

	/**
	 * Google setup wizard modal renderer.
	 *
	 * @var GoogleWizardModalView
	 */
	private GoogleWizardModalView $wizard_modal;

	/**
	 * Construct the mode-panel renderer.
	 *
	 * @param FormFieldView|null         $fields       Shared field renderer.
	 * @param GoogleWizardModalView|null $wizard_modal Google setup wizard modal renderer.
	 */
	public function __construct( ?FormFieldView $fields = null, ?GoogleWizardModalView $wizard_modal = null ) {
		$this->fields       = $fields ?? new FormFieldView();
		$this->wizard_modal = $wizard_modal ?? new GoogleWizardModalView();
	}

	/**
	 * Render the mode-dependent settings panels.
	 *
	 * @param Settings                                                $settings              Current settings.
	 * @param GoogleSettings                                          $google_settings       Current Google settings.
	 * @param string                                                  $google_status         Current Google connection status.
	 * @param bool                                                    $google_secret_saved   Whether a client secret is already stored.
	 * @param string                                                  $google_connect_url    Connect action URL.
	 * @param string                                                  $google_disconnect_url Disconnect action URL.
	 * @param string                                                  $google_notice         One-shot Google notice text.
	 * @param string                                                  $google_notice_type    One-shot Google notice type.
	 * @param array<int, array{site_url: string, permission: string}> $google_properties Cached properties.
	 * @param string                                                  $property_refresh_url  Property refresh action URL.
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
		string $google_notice_type,
		array $google_properties = array(),
		string $property_refresh_url = ''
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
			$google_notice_type,
			$google_properties,
			$property_refresh_url
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
	 * @param Settings                                                $settings              Current settings.
	 * @param GoogleSettings                                          $google_settings       Current Google settings.
	 * @param string                                                  $google_status         Current Google connection status.
	 * @param bool                                                    $google_secret_saved   Whether a client secret is already stored.
	 * @param string                                                  $google_connect_url    Connect action URL.
	 * @param string                                                  $google_disconnect_url Disconnect action URL.
	 * @param string                                                  $google_notice         One-shot Google notice text.
	 * @param string                                                  $google_notice_type    One-shot Google notice type.
	 * @param array<int, array{site_url: string, permission: string}> $google_properties Cached properties.
	 * @param string                                                  $property_refresh_url  Property refresh action URL.
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
		string $google_notice_type,
		array $google_properties = array(),
		string $property_refresh_url = ''
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
			$google_notice_type,
			$google_properties,
			$property_refresh_url
		);
		echo '</div>';
	}

	/**
	 * Render the Google connect/configuration controls for Blog mode.
	 *
	 * @param GoogleSettings                                          $settings       Current Google settings.
	 * @param string                                                  $status         Connection status.
	 * @param bool                                                    $secret_saved   Whether a client secret is already stored.
	 * @param string                                                  $connect_url    Connect action URL.
	 * @param string                                                  $disconnect_url Disconnect action URL.
	 * @param string                                                  $notice         One-shot notice text.
	 * @param string                                                  $notice_type    Notice type.
	 * @param array<int, array{site_url: string, permission: string}> $google_properties Cached properties.
	 * @param string                                                  $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	private function render_google_panel(
		GoogleSettings $settings,
		string $status,
		bool $secret_saved,
		string $connect_url,
		string $disconnect_url,
		string $notice,
		string $notice_type,
		array $google_properties,
		string $property_refresh_url
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
		$this->wizard_modal->render(
			$settings,
			$status,
			$secret_saved,
			$connect_url,
			$disconnect_url,
			$notice,
			$google_properties,
			$property_refresh_url
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
			GoogleTokenStore::status_label( $status ),
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
}
