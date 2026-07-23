<?php
/**
 * Renders the Google setup wizard page shell.
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
 * Presentation-only shell for the stepped Google setup wizard: page chrome,
 * notices, and the clickable progress stepper. Step bodies are delegated to
 * the setup/account step views so each class stays within the size budget.
 */
final class GoogleWizardView {
	/**
	 * Pre-connect step renderer (signal, app, credentials).
	 *
	 * @var GoogleWizardSetupStepsView
	 */
	private GoogleWizardSetupStepsView $setup_steps;

	/**
	 * Account step renderer (connect, property, done).
	 *
	 * @var GoogleWizardAccountStepsView
	 */
	private GoogleWizardAccountStepsView $account_steps;

	/**
	 * Construct the shell.
	 *
	 * @param GoogleWizardSetupStepsView|null   $setup_steps   Pre-connect step renderer.
	 * @param GoogleWizardAccountStepsView|null $account_steps Account step renderer.
	 */
	public function __construct(
		?GoogleWizardSetupStepsView $setup_steps = null,
		?GoogleWizardAccountStepsView $account_steps = null
	) {
		$this->setup_steps   = $setup_steps ?? new GoogleWizardSetupStepsView();
		$this->account_steps = $account_steps ?? new GoogleWizardAccountStepsView();
	}

	/**
	 * Render the wizard page.
	 *
	 * @param string                                                                             $step         The step to show.
	 * @param array<string, array{n: int, label: string, done: bool, reachable: bool}>           $steps        Stepper metadata, in order.
	 * @param GoogleSettings                                                                     $settings     Current Google settings.
	 * @param string                                                                             $status       Connection status.
	 * @param array<int, array{site_url: string, permission: string}>                            $properties   Cached Search Console properties.
	 * @param array<int, array{property_id: string, display_name: string, account_name: string}> $ga4_properties Cached GA4 properties.
	 * @param string                                                                             $signal       Chosen signal path.
	 * @param bool                                                                               $saved        Whether this request saved Google settings.
	 * @param string                                                                             $import_error Client JSON import error, or ''.
	 * @param string                                                                             $notice       One-shot notice text.
	 * @param string                                                                             $notice_type  One-shot notice type.
	 * @param bool                                                                               $analytics_ready Whether the connection includes Analytics access.
	 * @return void
	 */
	public function render(
		string $step,
		array $steps,
		GoogleSettings $settings,
		string $status,
		array $properties,
		array $ga4_properties,
		string $signal,
		bool $saved,
		string $import_error,
		string $notice,
		string $notice_type,
		bool $analytics_ready = false
	): void {
		echo '<div class="cf-app-container cf-wizard-container">';
		$this->render_header();
		echo '<main class="cf-wizard-page">';
		$this->render_notices( $saved, $import_error, $notice, $notice_type );
		$this->render_stepper( $steps, $step, $signal );
		echo '<div class="cf-card cf-wizard-card">';
		$this->render_step_body( $step, $settings, $status, $properties, $ga4_properties, $signal, $analytics_ready );
		echo '</div>';
		echo '</main>';
		echo '</div>';
	}

	/**
	 * Render the wizard page header.
	 *
	 * @return void
	 */
	private function render_header(): void {
		echo '<header class="cf-app-header">';
		echo '<div class="cf-header-left">';
		echo '<h1>' . esc_html__( 'Google setup', 'cannyforge-archive' ) . '</h1>';
		echo '</div>';
		echo '<div class="cf-header-right">';
		printf(
			'<a class="cf-btn cf-btn-outline" href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . SettingsPage::PAGE_SLUG ) ),
			esc_html__( 'Back to settings', 'cannyforge-archive' )
		);
		echo '</div>';
		echo '</header>';
	}

	/**
	 * Render the save/import/one-shot notices above the stepper.
	 *
	 * @param bool   $saved        Whether this request saved Google settings.
	 * @param string $import_error Client JSON import error, or ''.
	 * @param string $notice       One-shot notice text.
	 * @param string $notice_type  One-shot notice type.
	 * @return void
	 */
	private function render_notices( bool $saved, string $import_error, string $notice, string $notice_type ): void {
		if ( $saved && '' === $import_error ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Google settings saved.', 'cannyforge-archive' ) . '</p></div>';
		}

		if ( '' !== $import_error ) {
			echo '<div class="notice notice-error inline"><p>';
			echo esc_html__( 'Google OAuth client JSON was not imported: ', 'cannyforge-archive' ) . esc_html( $import_error );
			echo '</p></div>';
		}

		if ( '' !== $notice ) {
			$class = GoogleConnectionController::NOTICE_SUCCESS === $notice_type ? 'notice-success' : 'notice-error';
			printf( '<div class="notice %1$s inline"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $notice ) );
		}
	}

	/**
	 * Render the progress stepper: done steps green with a check, the active
	 * step highlighted, upcoming steps neutral. Reachable steps are links.
	 *
	 * @param array<string, array{n: int, label: string, done: bool, reachable: bool}> $steps  Stepper metadata.
	 * @param string                                                                   $active The active step key.
	 * @param string                                                                   $signal Chosen signal path, carried on step links.
	 * @return void
	 */
	private function render_stepper( array $steps, string $active, string $signal ): void {
		echo '<ol class="cf-wizard-steps">';
		foreach ( $steps as $key => $meta ) {
			$is_active = $key === $active;
			$class     = 'cf-wizard-step-pill' . ( $is_active ? ' is-active' : ( $meta['done'] ? ' is-done' : '' ) );
			$label     = ( $meta['done'] && ! $is_active ? '✓ ' : $meta['n'] . '. ' ) . $meta['label'];

			echo '<li>';
			if ( $meta['reachable'] && ! $is_active ) {
				printf(
					'<a class="%1$s" href="%2$s">%3$s</a>',
					esc_attr( $class ),
					esc_url( add_query_arg( array( GoogleWizardPage::SIGNAL_FIELD => $signal ), GoogleWizardPage::url( $key ) ) ),
					esc_html( $label )
				);
			} else {
				printf(
					'<span class="%1$s"%2$s>%3$s</span>',
					esc_attr( $class ),
					$is_active ? ' aria-current="step"' : '',
					esc_html( $label )
				);
			}
			echo '</li>';
		}
		echo '</ol>';
	}

	/**
	 * Render the body of the active step.
	 *
	 * @param string                                                                             $step       The step to show.
	 * @param GoogleSettings                                                                     $settings   Current Google settings.
	 * @param string                                                                             $status     Connection status.
	 * @param array<int, array{site_url: string, permission: string}>                            $properties Cached Search Console properties.
	 * @param array<int, array{property_id: string, display_name: string, account_name: string}> $ga4_properties Cached GA4 properties.
	 * @param string                                                                             $signal     Chosen signal path.
	 * @param bool                                                                               $analytics_ready Whether the connection includes Analytics access.
	 * @return void
	 */
	private function render_step_body( string $step, GoogleSettings $settings, string $status, array $properties, array $ga4_properties, string $signal, bool $analytics_ready ): void {
		match ( $step ) {
			GoogleWizardPage::STEP_SIGNAL      => $this->setup_steps->signal( $signal ),
			GoogleWizardPage::STEP_APP         => $this->setup_steps->app( $signal ),
			GoogleWizardPage::STEP_CREDENTIALS => $this->setup_steps->credentials( $settings, $signal ),
			GoogleWizardPage::STEP_CONNECT     => $this->account_steps->connect( $settings, $status, $signal ),
			GoogleWizardPage::STEP_PROPERTY    => $this->account_steps->property( $settings, $properties, $ga4_properties, $signal, $analytics_ready ),
			default                            => $this->account_steps->done( $settings, $status, $signal ),
		};
	}
}
