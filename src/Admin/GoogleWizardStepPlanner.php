<?php
/**
 * Step gating and progression logic for the Google setup wizard.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides which wizard step to show and how the stepper renders: the
 * state-derived default step, reachability gating for explicit step
 * requests, and the per-step done/reachable metadata.
 */
final class GoogleWizardStepPlanner {
	/**
	 * All step keys, in wizard order.
	 */
	private const STEPS = array(
		GoogleWizardPage::STEP_SIGNAL,
		GoogleWizardPage::STEP_APP,
		GoogleWizardPage::STEP_CREDENTIALS,
		GoogleWizardPage::STEP_CONNECT,
		GoogleWizardPage::STEP_PROPERTY,
		GoogleWizardPage::STEP_DONE,
	);

	/**
	 * Resolve the step to show: the explicitly requested step when it is
	 * reachable, otherwise the first incomplete step.
	 *
	 * @param array{has_credentials: bool, connected: bool, has_property: bool, has_ga4: bool} $state Gating state.
	 * @return string
	 */
	public function resolve_step( array $state ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only routing value.
		$requested = isset( $_GET[ GoogleWizardPage::QUERY_STEP ] ) ? sanitize_key( wp_unslash( $_GET[ GoogleWizardPage::QUERY_STEP ] ) ) : '';

		if ( in_array( $requested, self::STEPS, true ) && $this->is_reachable( $requested, $state ) ) {
			return $requested;
		}

		return $this->default_step( $state );
	}

	/**
	 * The state-derived default step: the next action needed.
	 *
	 * @param array{has_credentials: bool, connected: bool, has_property: bool, has_ga4: bool} $state Gating state.
	 * @return string
	 */
	private function default_step( array $state ): string {
		if ( ! $state['has_credentials'] ) {
			return GoogleWizardPage::STEP_SIGNAL;
		}
		if ( ! $state['connected'] ) {
			return GoogleWizardPage::STEP_CONNECT;
		}
		if ( ! $state['has_property'] ) {
			return GoogleWizardPage::STEP_PROPERTY;
		}

		return GoogleWizardPage::STEP_DONE;
	}

	/**
	 * Whether a step can be visited given the current state.
	 *
	 * The three setup steps and the summary are always reachable; Connect
	 * needs saved credentials, and Property needs a live connection.
	 *
	 * @param string                                                                           $step  Step key.
	 * @param array{has_credentials: bool, connected: bool, has_property: bool, has_ga4: bool} $state Gating state.
	 * @return bool
	 */
	private function is_reachable( string $step, array $state ): bool {
		return match ( $step ) {
			GoogleWizardPage::STEP_CONNECT  => $state['has_credentials'],
			GoogleWizardPage::STEP_PROPERTY => $state['connected'],
			default                         => true,
		};
	}

	/**
	 * Build the stepper metadata: number, label, done and reachable flags.
	 *
	 * @param array{has_credentials: bool, connected: bool, has_property: bool, has_ga4: bool} $state Gating state.
	 * @return array<string, array{n: int, label: string, done: bool, reachable: bool}>
	 */
	public function steps_meta( array $state ): array {
		$done = array(
			GoogleWizardPage::STEP_SIGNAL      => $state['has_credentials'],
			GoogleWizardPage::STEP_APP         => $state['has_credentials'],
			GoogleWizardPage::STEP_CREDENTIALS => $state['has_credentials'],
			GoogleWizardPage::STEP_CONNECT     => $state['connected'],
			GoogleWizardPage::STEP_PROPERTY    => $state['has_property'],
			GoogleWizardPage::STEP_DONE        => $state['has_credentials'] && $state['connected'] && $state['has_property'],
		);

		$labels = array(
			GoogleWizardPage::STEP_SIGNAL      => __( 'Signal', 'cannyforge-archive' ),
			GoogleWizardPage::STEP_APP         => __( 'Google app', 'cannyforge-archive' ),
			GoogleWizardPage::STEP_CREDENTIALS => __( 'Credentials', 'cannyforge-archive' ),
			GoogleWizardPage::STEP_CONNECT     => __( 'Connect', 'cannyforge-archive' ),
			GoogleWizardPage::STEP_PROPERTY    => __( 'Property', 'cannyforge-archive' ),
			GoogleWizardPage::STEP_DONE        => __( 'Finish', 'cannyforge-archive' ),
		);

		$meta = array();
		foreach ( self::STEPS as $index => $step ) {
			$meta[ $step ] = array(
				'n'         => $index + 1,
				'label'     => $labels[ $step ],
				'done'      => $done[ $step ],
				'reachable' => $this->is_reachable( $step, $state ),
			);
		}

		return $meta;
	}

	/**
	 * Whether a raw value names a wizard step.
	 *
	 * @param string $step Candidate step key.
	 * @return bool
	 */
	public static function is_step( string $step ): bool {
		return in_array( $step, self::STEPS, true );
	}

	/**
	 * Read the posted wizard signal, defaulting to Search Console.
	 *
	 * @return string
	 */
	public static function signal_from_request(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller has verified the connect nonce.
		$signal = isset( $_POST[ GoogleWizardPage::SIGNAL_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ GoogleWizardPage::SIGNAL_FIELD ] ) ) : '';

		return in_array( $signal, array( GoogleWizardPage::SIGNAL_SC, GoogleWizardPage::SIGNAL_GA4, GoogleWizardPage::SIGNAL_SC_GA4 ), true )
			? $signal
			: GoogleWizardPage::SIGNAL_SC;
	}

	/**
	 * Derive a signal path from the scopes requested for a callback.
	 *
	 * @param bool $analytics      Whether Analytics was requested.
	 * @param bool $search_console Whether Search Console was requested.
	 * @return string
	 */
	public static function signal_from_scope_flags( bool $analytics, bool $search_console ): string {
		if ( $analytics && $search_console ) {
			return GoogleWizardPage::SIGNAL_SC_GA4;
		}
		if ( $analytics ) {
			return GoogleWizardPage::SIGNAL_GA4;
		}

		return GoogleWizardPage::SIGNAL_SC;
	}
}
