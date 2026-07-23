<?php
/**
 * Renders the account-facing Google wizard steps.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleOauthScopePolicy;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Presentation-only bodies for the Connect, Property, and Finish steps.
 *
 * The Connect and refresh buttons post to the existing admin-post handlers;
 * every such button carries the wizard return-step field so the handler's
 * redirect lands back inside the wizard instead of on the settings page.
 */
final class GoogleWizardAccountStepsView {
	/**
	 * Render step 4: connect the Google account.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @param string         $signal   Selected content signal.
	 * @return void
	 */
	public function connect( GoogleSettings $settings, string $status, string $signal = GoogleWizardPage::SIGNAL_SC ): void {
		$connected           = GoogleTokenStore::STATUS_CONNECTED === $status;
		$uses_search_console = GoogleWizardPage::signal_uses_search_console( $signal );
		$uses_analytics      = GoogleWizardPage::signal_uses_analytics( $signal );

		echo '<h2>' . esc_html__( 'Connect your Google account', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">';
		if ( $uses_search_console && $uses_analytics ) {
			echo esc_html__( 'Connect the Google account that has access to your Search Console property and GA4. If the Google app is unpublished, that account must be listed as a test user.', 'cannyforge-archive' );
		} elseif ( $uses_analytics ) {
			echo esc_html__( 'Connect the Google account that has access to your GA4 property. If the Google app is unpublished, that account must be listed as a test user.', 'cannyforge-archive' );
		} else {
			echo esc_html__( 'Connect the Google account that has access to your Search Console property. If the Google app is unpublished, that account must be listed as a test user.', 'cannyforge-archive' );
		}
		echo '</p>';

		$this->consent_copy( $settings, $uses_search_console, $uses_analytics );
		$this->status_pill( $status );

		if ( GoogleTokenStore::STATUS_NEEDS_REAUTH === $status ) {
			echo '<p class="description">';
			echo esc_html__( "Connection needs re-authorising — the site's security keys may have changed, so the stored connection can no longer be read. Click Connect Google again to restore it; nothing else needs to change.", 'cannyforge-archive' );
			echo '</p>';
		}

		printf( '<form method="post" action="%s">', esc_url( GoogleWizardPage::url( GoogleWizardPage::STEP_CONNECT ) ) );
		printf( '<input type="hidden" name="%s" value="%s">', esc_attr( GoogleWizardPage::SIGNAL_FIELD ), esc_attr( $signal ) );
		$this->return_step_field( GoogleWizardPage::STEP_CONNECT );
		wp_nonce_field( GoogleConnectionController::CONNECT_NONCE_ACTION, GoogleConnectionController::CONNECT_NONCE_FIELD );
		wp_nonce_field( GoogleConnectionController::DISCONNECT_NONCE_ACTION, GoogleConnectionController::DISCONNECT_NONCE_FIELD );

		echo '<div class="cf-wizard-actions">';
		printf(
			'<button type="submit" class="cf-btn cf-btn-primary" formaction="%s">%s</button>',
			esc_url( $this->action_url( GoogleConnectionController::ACTION_CONNECT ) ),
			esc_html( $connected ? __( 'Reconnect Google', 'cannyforge-archive' ) : __( 'Connect Google', 'cannyforge-archive' ) )
		);
		if ( $connected ) {
			printf(
				'<button type="submit" class="cf-btn cf-btn-outline" formaction="%s">%s</button>',
				esc_url( $this->action_url( GoogleConnectionController::ACTION_DISCONNECT ) ),
				esc_html__( 'Disconnect', 'cannyforge-archive' )
			);
		}
		echo '</div>';

		echo '<div class="cf-wizard-nav">';
		$this->back_link( GoogleWizardPage::STEP_CREDENTIALS, $signal );
		if ( $connected ) {
			printf(
				'<a class="cf-btn cf-btn-primary" href="%s">%s</a>',
				esc_url( add_query_arg( array( GoogleWizardPage::SIGNAL_FIELD => $signal ), GoogleWizardPage::url( GoogleWizardPage::STEP_PROPERTY ) ) ),
				esc_html__( 'Continue', 'cannyforge-archive' )
			);
		}
		echo '</div>';
		echo '</form>';
	}

	/**
	 * Render the scopes-to-be-requested consent copy shown before Connect.
	 *
	 * Renders exactly the scope set returned by the central OAuth policy, so
	 * the visible consent copy cannot omit a scope the redirect requests.
	 *
	 * @param GoogleSettings $settings           Current Google settings.
	 * @param bool           $include_search_console Whether the selected wizard signal needs Search Console access.
	 * @param bool           $include_analytics  Whether the selected wizard signal needs Analytics access.
	 * @return void
	 */
	private function consent_copy( GoogleSettings $settings, bool $include_search_console = true, bool $include_analytics = false ): void {
		$labels       = array(
			GoogleOauthScopePolicy::SCOPE_SEARCH_CONSOLE => __( 'Search Console (read-only)', 'cannyforge-archive' ),
			GoogleOauthScopePolicy::SCOPE_ANALYTICS      => __( 'Google Analytics 4 (read-only)', 'cannyforge-archive' ),
		);
		$scope_labels = array_map(
			static fn ( string $scope ): string => $labels[ $scope ] ?? $scope,
			GoogleOauthScopePolicy::scopes( $settings, $include_analytics, $include_search_console )
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
	 * Render step 5: choose the Search Console property and report window.
	 *
	 * @param GoogleSettings                                                                     $settings   Current Google settings.
	 * @param array<int, array{site_url: string, permission: string}>                            $properties Cached properties.
	 * @param array<int, array{property_id: string, display_name: string, account_name: string}> $ga4_properties Cached GA4 properties.
	 * @param string                                                                             $signal      Selected content signal.
	 * @param bool                                                                               $analytics_ready Whether the connection includes Analytics access.
	 * @return void
	 */
	public function property( GoogleSettings $settings, array $properties, array $ga4_properties = array(), string $signal = GoogleWizardPage::SIGNAL_SC, bool $analytics_ready = true ): void {
		$uses_search_console = GoogleWizardPage::signal_uses_search_console( $signal );
		$uses_analytics      = GoogleWizardPage::signal_uses_analytics( $signal );
		$title               = $uses_search_console && $uses_analytics
			? __( 'Choose your Search Console and Analytics properties', 'cannyforge-archive' )
			: ( $uses_analytics ? __( 'Choose your Analytics property', 'cannyforge-archive' ) : __( 'Choose your Search Console property', 'cannyforge-archive' ) );
		$description         = $uses_search_console && $uses_analytics
			? __( 'Choose both properties used by this path. The archive uses Search Console first and falls back to GA4 when Search Console has no data.', 'cannyforge-archive' )
			: ( $uses_analytics ? __( 'Choose the GA4 property whose top pages should populate the archive.', 'cannyforge-archive' ) : __( 'The properties available to the connected account load automatically after connecting. If the list is empty, use Load properties — and make sure this site is listed in Search Console.', 'cannyforge-archive' ) );

		echo '<h2>' . esc_html( $title ) . '</h2>';
		echo '<p class="description">';
		echo esc_html( $description );
		echo '</p>';

		printf( '<form method="post" action="%s">', esc_url( GoogleWizardPage::url( GoogleWizardPage::STEP_DONE ) ) );
		wp_nonce_field( GoogleWizardPage::NONCE_ACTION, GoogleWizardPage::NONCE_FIELD );
		wp_nonce_field( SearchConsolePropertyController::NONCE_ACTION, SearchConsolePropertyController::NONCE_FIELD );
		wp_nonce_field( Ga4PropertyController::NONCE_ACTION, Ga4PropertyController::NONCE_FIELD );
		printf( '<input type="hidden" name="%s" value="%s">', esc_attr( GoogleWizardPage::SIGNAL_FIELD ), esc_attr( $signal ) );
		$this->return_step_field( GoogleWizardPage::STEP_PROPERTY );

		if ( $uses_search_console ) {
			( new GooglePropertySelectorView() )->render(
				$settings,
				$properties,
				$this->action_url( SearchConsolePropertyController::ACTION_REFRESH )
			);
		} else {
			printf( '<input type="hidden" name="google_search_console_site_url" value="">' );
		}
		if ( $uses_analytics ) {
			( new GoogleAnalyticsPropertySelectorView() )->render(
				$settings,
				$ga4_properties,
				$this->action_url( Ga4PropertyController::ACTION_REFRESH ),
				$analytics_ready
			);
		} else {
			printf( '<input type="hidden" name="google_ga4_property_id" value="">' );
		}

		printf(
			'<p><label>%s <input type="number" min="1" max="365" step="1" name="google_report_window_days" value="%d"></label></p>',
			esc_html__( 'Report window (days)', 'cannyforge-archive' ),
			absint( $settings->report_window_days() )
		);
		echo '<p class="description">' . esc_html__( 'How far back the top-content reports look.', 'cannyforge-archive' ) . '</p>';

		echo '<div class="cf-wizard-nav">';
		$this->back_link( GoogleWizardPage::STEP_CONNECT, $signal );
		echo '<button type="submit" class="cf-btn cf-btn-primary">' . esc_html__( 'Save and finish', 'cannyforge-archive' ) . '</button>';
		echo '</div>';
		echo '</form>';
	}

	/**
	 * Render step 6: the setup summary checklist and cache actions.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @param string         $signal   Selected content signal.
	 * @return void
	 */
	public function done( GoogleSettings $settings, string $status, string $signal = GoogleWizardPage::SIGNAL_SC ): void {
		echo '<h2>' . esc_html__( 'Setup summary', 'cannyforge-archive' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Green items are ready; amber items still need a step finished.', 'cannyforge-archive' ) . '</p>';

		$this->checklist( $settings, $status, $signal );
		$this->refresh_actions( $settings, $status, $signal );

		echo '<div class="cf-wizard-nav">';
		$this->back_link( GoogleWizardPage::STEP_PROPERTY, $signal );
		printf(
			'<a class="cf-btn cf-btn-primary" href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG ) ),
			esc_html__( 'Back to settings', 'cannyforge-archive' )
		);
		echo '</div>';
	}

	/**
	 * Render the green/amber setup checklist.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @param string         $signal   Selected content signal.
	 * @return void
	 */
	private function checklist( GoogleSettings $settings, string $status, string $signal ): void {
		$uses_search_console = GoogleWizardPage::signal_uses_search_console( $signal );
		$uses_analytics      = GoogleWizardPage::signal_uses_analytics( $signal );
		echo '<ul class="cf-wizard-checklist">';
		$this->checklist_row(
			__( 'Credentials saved', 'cannyforge-archive' ),
			'' !== $settings->client_id() && $settings->has_client_secret(),
			GoogleWizardPage::STEP_CREDENTIALS,
			false,
			$signal
		);
		$this->checklist_row(
			__( 'Google account connected', 'cannyforge-archive' ),
			GoogleTokenStore::STATUS_CONNECTED === $status,
			GoogleWizardPage::STEP_CONNECT,
			false,
			$signal
		);
		if ( $uses_search_console ) {
			$this->checklist_row(
				__( 'Search Console property chosen', 'cannyforge-archive' ),
				'' !== $settings->search_console_site_url(),
				GoogleWizardPage::STEP_PROPERTY,
				false,
				$signal
			);
		}
		if ( $uses_analytics ) {
			$this->checklist_row(
				$uses_search_console ? __( 'GA4 fallback property chosen', 'cannyforge-archive' ) : __( 'Analytics property chosen', 'cannyforge-archive' ),
				'' !== $settings->ga4_property_id(),
				GoogleWizardPage::STEP_PROPERTY,
				false,
				$signal
			);
		} elseif ( $uses_search_console ) {
			$this->checklist_row(
				__( 'GA4 fallback (optional)', 'cannyforge-archive' ),
				false,
				GoogleWizardPage::STEP_PROPERTY,
				true,
				$signal
			);
		}
		echo '</ul>';
	}

	/**
	 * Render one checklist row: a green check, or an amber marker with a
	 * "Finish this step" link.
	 *
	 * @param string $label    Row label.
	 * @param bool   $done     Whether the item is ready.
	 * @param string $fix_step Step that completes the item.
	 * @param bool   $optional Whether the item is optional (never amber).
	 * @param string $signal   Selected content signal.
	 * @return void
	 */
	private function checklist_row( string $label, bool $done, string $fix_step, bool $optional = false, string $signal = GoogleWizardPage::SIGNAL_SC ): void {
		$class = $done ? 'is-done' : ( $optional ? 'is-optional' : 'is-pending' );
		$mark  = $done ? '✓' : ( $optional ? '—' : '!' );

		printf( '<li class="%s"><span class="cf-wizard-checklist-mark" aria-hidden="true">%s</span> %s', esc_attr( $class ), esc_html( $mark ), esc_html( $label ) );
		if ( ! $done && ! $optional ) {
			printf(
				' <a href="%s">%s</a>',
				esc_url( add_query_arg( array( GoogleWizardPage::SIGNAL_FIELD => $signal ), GoogleWizardPage::url( $fix_step ) ) ),
				esc_html__( 'Finish this step', 'cannyforge-archive' )
			);
		}
		if ( ! $done && $optional ) {
			printf(
				' <a href="%s">%s</a>',
				esc_url( add_query_arg( array( GoogleWizardPage::SIGNAL_FIELD => GoogleWizardPage::SIGNAL_SC_GA4 ), GoogleWizardPage::url( $fix_step ) ) ),
				esc_html__( 'Add it', 'cannyforge-archive' )
			);
		}
		echo '</li>';
	}

	/**
	 * Render the cache refresh actions once a refresh can succeed.
	 *
	 * @param GoogleSettings $settings Current Google settings.
	 * @param string         $status   Connection status.
	 * @param string         $signal   Selected content signal.
	 * @return void
	 */
	private function refresh_actions( GoogleSettings $settings, string $status, string $signal ): void {
		$uses_search_console = GoogleWizardPage::signal_uses_search_console( $signal );
		$uses_analytics      = GoogleWizardPage::signal_uses_analytics( $signal );
		$ready               = GoogleTokenStore::STATUS_CONNECTED === $status
			&& ( ( $uses_search_console && '' !== $settings->search_console_site_url() ) || ( $uses_analytics && '' !== $settings->ga4_property_id() ) );
		if ( ! $ready ) {
			return;
		}

		printf( '<form method="post" action="%s">', esc_url( GoogleWizardPage::url( GoogleWizardPage::STEP_DONE ) ) );
		$this->return_step_field( GoogleWizardPage::STEP_DONE );
		printf( '<input type="hidden" name="%s" value="%s">', esc_attr( GoogleWizardPage::SIGNAL_FIELD ), esc_attr( $signal ) );
		wp_nonce_field( SearchConsoleRefreshController::REFRESH_NONCE_ACTION, SearchConsoleRefreshController::REFRESH_NONCE_FIELD );
		wp_nonce_field( Ga4RefreshController::REFRESH_NONCE_ACTION, Ga4RefreshController::REFRESH_NONCE_FIELD );

		echo '<p class="description">' . esc_html__( 'Populate the archive now instead of waiting for the next scheduled refresh:', 'cannyforge-archive' ) . '</p>';
		echo '<div class="cf-wizard-actions">';
		if ( $uses_search_console ) {
			printf(
				'<button type="submit" class="cf-btn cf-btn-outline" formaction="%s">%s</button>',
				esc_url( $this->action_url( SearchConsoleRefreshController::ACTION_REFRESH ) ),
				esc_html__( 'Refresh Search Console', 'cannyforge-archive' )
			);
		}
		if ( $uses_analytics && '' !== $settings->ga4_property_id() ) {
			printf(
				'<button type="submit" class="cf-btn cf-btn-outline" formaction="%s">%s</button>',
				esc_url( $this->action_url( Ga4RefreshController::ACTION_REFRESH ) ),
				esc_html__( 'Refresh GA4', 'cannyforge-archive' )
			);
		}
		echo '</div>';
		echo '</form>';
	}

	/**
	 * Render the connection status pill.
	 *
	 * @param string $status Connection status.
	 * @return void
	 */
	private function status_pill( string $status ): void {
		$styles = match ( $status ) {
			GoogleTokenStore::STATUS_CONNECTED => array(
				'background' => '#e8fff4',
				'color'      => '#0f7a43',
			),
			GoogleTokenStore::STATUS_EXPIRED, GoogleTokenStore::STATUS_NEEDS_REAUTH => array(
				'background' => '#fff4e5',
				'color'      => '#a05a00',
			),
			GoogleTokenStore::STATUS_ERROR => array(
				'background' => '#ffeaea',
				'color'      => '#b42318',
			),
			default => array(
				'background' => '#f0f2f5',
				'color'      => '#475467',
			),
		};
		printf(
			'<p><strong>%s</strong> <span class="cf-wizard-status-pill" style="background:%s;color:%s;">%s</span></p>',
			esc_html__( 'Connection status:', 'cannyforge-archive' ),
			esc_attr( $styles['background'] ),
			esc_attr( $styles['color'] ),
			esc_html( GoogleTokenStore::status_label( $status ) )
		);
	}

	/**
	 * Render the hidden wizard return-step field for admin-post buttons.
	 *
	 * @param string $step Step to return to after the action.
	 * @return void
	 */
	private function return_step_field( string $step ): void {
		printf(
			'<input type="hidden" name="%s" value="%s">',
			esc_attr( GoogleWizardPage::RETURN_STEP_FIELD ),
			esc_attr( $step )
		);
	}

	/**
	 * Build an admin-post URL for an action.
	 *
	 * @param string $action Admin-post action name.
	 * @return string
	 */
	private function action_url( string $action ): string {
		return admin_url( 'admin-post.php?action=' . $action );
	}

	/**
	 * Render the footer Back link.
	 *
	 * @param string $step Target step key.
	 * @param string $signal Selected content signal.
	 * @return void
	 */
	private function back_link( string $step, string $signal = GoogleWizardPage::SIGNAL_SC ): void {
		printf(
			'<a class="cf-btn cf-btn-text" href="%s">%s</a>',
			esc_url( add_query_arg( array( GoogleWizardPage::SIGNAL_FIELD => $signal ), GoogleWizardPage::url( $step ) ) ),
			esc_html__( 'Back', 'cannyforge-archive' )
		);
	}
}
