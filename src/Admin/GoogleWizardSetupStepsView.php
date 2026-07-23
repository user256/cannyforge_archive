<?php
/**
 * Renders the pre-connect Google wizard steps.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleSettings;

/**
 * Presentation-only bodies for the Signal, Google app, and Credentials steps.
 *
 * Each step owns its own form (a GET form for pure navigation, a POST form
 * for the credentials save), so the wizard works without JavaScript and every
 * transition is an ordinary page load — the same shape as the appliance
 * setup wizard this screen is modelled on.
 */
final class GoogleWizardSetupStepsView {
	/**
	 * Render step 1: choose the signal path (radio cards).
	 *
	 * @param string $signal Chosen signal path.
	 * @return void
	 */
	public function signal( string $signal ): void {
		echo '<h2>' . esc_html__( 'Choose your content signal', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'Choose Search Console, Analytics, or a combined path that uses GA4 when Search Console returns no data.', 'cannyforge-archive' );
		echo '</p>';

		printf( '<form method="get" action="%s">', esc_url( admin_url( 'admin.php' ) ) );
		echo '<input type="hidden" name="page" value="' . esc_attr( SettingsPage::PAGE_SLUG ) . '">';
		echo '<input type="hidden" name="' . esc_attr( GoogleWizardPage::QUERY_FLAG ) . '" value="' . esc_attr( GoogleWizardPage::FLAG_VALUE ) . '">';
		echo '<input type="hidden" name="' . esc_attr( GoogleWizardPage::QUERY_STEP ) . '" value="' . esc_attr( GoogleWizardPage::STEP_APP ) . '">';

		echo '<div class="cf-mode-cards cf-signal-cards">';
		$this->signal_card(
			GoogleWizardPage::SIGNAL_SC,
			__( 'Search Console only', 'cannyforge-archive' ),
			__( 'Read-only access to your Search Console top content.', 'cannyforge-archive' ),
			'dashicons-search',
			GoogleWizardPage::SIGNAL_SC === $signal
		);
		$this->signal_card(
			GoogleWizardPage::SIGNAL_GA4,
			__( 'Analytics only', 'cannyforge-archive' ),
			__( 'Use GA4 top pages as the primary content signal.', 'cannyforge-archive' ),
			'dashicons-chart-bar',
			GoogleWizardPage::SIGNAL_GA4 === $signal
		);
		$this->signal_card(
			GoogleWizardPage::SIGNAL_SC_GA4,
			__( 'Search Console + GA4 fallback', 'cannyforge-archive' ),
			__( 'Use Search Console first, then GA4 when Search Console has no data.', 'cannyforge-archive' ),
			'dashicons-chart-area',
			GoogleWizardPage::SIGNAL_SC_GA4 === $signal
		);
		echo '</div>';

		echo '<div class="cf-wizard-nav">';
		echo '<span></span>';
		echo '<button type="submit" class="cf-btn cf-btn-primary">' . esc_html__( 'Continue', 'cannyforge-archive' ) . '</button>';
		echo '</div>';
		echo '</form>';
	}

	/**
	 * Render one signal radio card.
	 *
	 * @param string $value   Radio value.
	 * @param string $title   Card title.
	 * @param string $desc    Card description.
	 * @param string $icon    Dashicon class.
	 * @param bool   $checked Whether this card is selected.
	 * @return void
	 */
	private function signal_card( string $value, string $title, string $desc, string $icon, bool $checked ): void {
		echo '<label class="cf-mode-card">';
		printf(
			'<input type="radio" name="%s" value="%s" class="cf-visually-hidden"%s>',
			esc_attr( GoogleWizardPage::SIGNAL_FIELD ),
			esc_attr( $value ),
			checked( $checked, true, false )
		);
		echo '<div class="cf-mode-card-header">';
		echo '<div class="cf-radio-circle"><div class="cf-radio-dot"></div></div>';
		echo '<div class="cf-check-badge"><span class="dashicons dashicons-yes" aria-hidden="true"></span></div>';
		echo '</div>';
		echo '<div class="cf-mode-card-icon"><span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span></div>';
		echo '<h4>' . esc_html( $title ) . '</h4>';
		echo '<p>' . esc_html( $desc ) . '</p>';
		echo '</label>';
	}

	/**
	 * Render step 2: create the Google Cloud app, leading with the exact
	 * redirect URI the client must be created with.
	 *
	 * @param string $signal Chosen signal path.
	 * @return void
	 */
	public function app( string $signal ): void {
		echo '<h2>' . esc_html__( 'Create the Google app', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'One-time setup in Google Cloud: a project with the required Google APIs enabled and an OAuth web client. Start by copying this Redirect URI — the client must be created with it, exactly as shown.', 'cannyforge-archive' );
		echo '</p>';

		$this->redirect_uri_field();
		$this->app_instructions( $signal );

		echo '<div class="cf-wizard-nav">';
		$this->back_link( GoogleWizardPage::STEP_SIGNAL, $signal );
		printf(
			'<a class="cf-btn cf-btn-primary" href="%s">%s</a>',
			esc_url( $this->step_url( GoogleWizardPage::STEP_CREDENTIALS, $signal ) ),
			esc_html__( 'Continue', 'cannyforge-archive' )
		);
		echo '</div>';
	}

	/**
	 * Render the copyable redirect URI field.
	 *
	 * @return void
	 */
	private function redirect_uri_field(): void {
		$uri = admin_url( 'admin-post.php?action=' . GoogleConnectionController::ACTION_CALLBACK );

		echo '<div class="cf-wizard-copy-row">';
		echo '<label for="cf-google-redirect-uri"><strong>' . esc_html__( 'Authorized redirect URI', 'cannyforge-archive' ) . '</strong></label>';
		echo '<div class="cf-wizard-copy-controls">';
		printf(
			'<input type="text" id="cf-google-redirect-uri" value="%s" readonly data-cf-select-on-focus>',
			esc_attr( $uri )
		);
		printf(
			'<button type="button" class="cf-btn cf-btn-outline" data-cf-copy="#cf-google-redirect-uri">%s</button>',
			esc_html__( 'Copy', 'cannyforge-archive' )
		);
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'It must match exactly, including https and the full admin-post.php?action=... path.', 'cannyforge-archive' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render the collapsible Google Cloud instructions.
	 *
	 * @param string $signal Chosen signal path.
	 * @return void
	 */
	private function app_instructions( string $signal ): void {
		echo '<details class="cf-wizard-details" open>';
		echo '<summary>' . esc_html__( '1. Set up Google Auth Platform', 'cannyforge-archive' ) . '</summary>';
		echo '<p>' . esc_html__( 'In Google Cloud, create or select a project, then open Google Auth Platform. If Google shows a Get started prompt for a new project, complete it first. If the app stays unpublished (External), add yourself as a test user during that flow.', 'cannyforge-archive' ) . '</p>';
		echo '<p><a class="button button-secondary" href="https://console.cloud.google.com/auth/branding" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Google Auth Platform', 'cannyforge-archive' ) . '</a></p>';
		echo '</details>';

		echo '<details class="cf-wizard-details">';
		echo '<summary>' . esc_html__( '2. Create the OAuth web client', 'cannyforge-archive' ) . '</summary>';
		echo '<p>' . esc_html__( 'Open Clients, click Create client, choose Web application, and name it (for example CannyForge Archive). Under Authorized redirect URIs click + Add URI, paste the Redirect URI above, then Create. JavaScript origins are not required.', 'cannyforge-archive' ) . '</p>';
		echo '<p><a class="button button-secondary" href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Clients', 'cannyforge-archive' ) . '</a></p>';
		echo '</details>';

		echo '<details class="cf-wizard-details">';
		echo '<summary>' . esc_html__( '3. Enable the APIs', 'cannyforge-archive' ) . '</summary>';
		$uses_search_console = GoogleWizardPage::signal_uses_search_console( $signal );
		$uses_analytics      = GoogleWizardPage::signal_uses_analytics( $signal );
		echo '<p>';
		if ( $uses_search_console && $uses_analytics ) {
			echo esc_html__( 'This path uses Search Console first and GA4 as a fallback, so enable all three APIs.', 'cannyforge-archive' );
		} elseif ( $uses_analytics ) {
			echo esc_html__( 'This path uses GA4 as the primary signal, so enable the Analytics Data API and Analytics Admin API.', 'cannyforge-archive' );
		} else {
			echo esc_html__( 'This path uses Search Console, so enable the Search Console API.', 'cannyforge-archive' );
		}
		echo '</p><p>';
		if ( $uses_search_console ) {
			echo '<a class="button button-secondary" href="https://console.cloud.google.com/apis/library/searchconsole.googleapis.com" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Enable Search Console API', 'cannyforge-archive' ) . '</a>';
		}
		if ( $uses_analytics ) {
			echo ' <a class="button button-secondary" href="https://console.cloud.google.com/apis/library/analyticsdata.googleapis.com" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Enable Analytics Data API', 'cannyforge-archive' ) . '</a>';
			echo ' <a class="button button-secondary" href="https://console.cloud.google.com/apis/library/analyticsadmin.googleapis.com" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Enable Analytics Admin API', 'cannyforge-archive' ) . '</a>';
		}
		echo '</p>';
		echo '</details>';
	}

	/**
	 * Render step 3: save the OAuth credentials (and the GA4 property when
	 * the fallback signal is chosen, so the connect step can request the
	 * Analytics scope).
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $signal   Chosen signal path.
	 * @return void
	 */
	public function credentials( GoogleSettings $settings, string $signal ): void {
		echo '<h2>' . esc_html__( 'Add your credentials', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		echo esc_html__( 'In Google Auth Platform > Clients, open your web client and use Download JSON, then upload the file here. The Client ID and Secret are imported securely on the server.', 'cannyforge-archive' );
		echo '</p>';

		printf(
			'<form method="post" enctype="multipart/form-data" action="%s">',
			esc_url( $this->step_url( GoogleWizardPage::STEP_CONNECT, $signal ) )
		);
		wp_nonce_field( GoogleWizardPage::NONCE_ACTION, GoogleWizardPage::NONCE_FIELD );
		printf( '<input type="hidden" name="%s" value="%s">', esc_attr( GoogleWizardPage::SIGNAL_FIELD ), esc_attr( $signal ) );

		echo '<p><label>' . esc_html__( 'OAuth client JSON', 'cannyforge-archive' ) . '<br>';
		echo '<input type="file" name="' . esc_attr( GoogleClientJsonUploadReader::FILE_FIELD ) . '" accept=".json,application/json"></label></p>';

		$this->manual_credential_fields( $settings );

		echo '<div class="cf-wizard-nav">';
		$this->back_link( GoogleWizardPage::STEP_APP, $signal );
		echo '<button type="submit" class="cf-btn cf-btn-primary">' . esc_html__( 'Save and continue', 'cannyforge-archive' ) . '</button>';
		echo '</div>';
		echo '</form>';
	}

	/**
	 * Render the manual Client ID / Secret fields inside a details block.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @return void
	 */
	private function manual_credential_fields( GoogleSettings $settings ): void {
		$secret_placeholder = $settings->has_client_secret()
			? __( 'Saved. Leave blank to keep it.', 'cannyforge-archive' )
			: __( 'Paste the client secret.', 'cannyforge-archive' );

		echo '<details class="cf-wizard-details"' . ( '' !== $settings->client_id() ? ' open' : '' ) . '>';
		echo '<summary>' . esc_html__( 'Or paste the values manually', 'cannyforge-archive' ) . '</summary>';
		printf(
			'<p><label>%s <input type="text" name="google_client_id" value="%s" autocomplete="off"></label></p>',
			esc_html__( 'Google Client ID', 'cannyforge-archive' ),
			esc_attr( $settings->client_id() )
		);
		printf(
			'<p><label>%s <input type="password" name="google_client_secret" value="" placeholder="%s" autocomplete="new-password"></label></p>',
			esc_html__( 'Google Client Secret', 'cannyforge-archive' ),
			esc_attr( $secret_placeholder )
		);
		echo '<p class="description">' . esc_html__( 'The client secret is never rendered back into the form. Leave it blank to keep the stored secret unchanged.', 'cannyforge-archive' ) . '</p>';
		echo '</details>';
	}

	/**
	 * A wizard step URL carrying the chosen signal.
	 *
	 * @param string $step   Step key.
	 * @param string $signal Chosen signal path.
	 * @return string
	 */
	private function step_url( string $step, string $signal ): string {
		return add_query_arg( array( GoogleWizardPage::SIGNAL_FIELD => $signal ), GoogleWizardPage::url( $step ) );
	}

	/**
	 * Render the footer Back link.
	 *
	 * @param string $step   Target step key.
	 * @param string $signal Chosen signal path.
	 * @return void
	 */
	private function back_link( string $step, string $signal ): void {
		printf(
			'<a class="cf-btn cf-btn-text" href="%s">%s</a>',
			esc_url( $this->step_url( $step, $signal ) ),
			esc_html__( 'Back', 'cannyforge-archive' )
		);
	}
}
