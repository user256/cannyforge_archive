<?php
/**
 * Renders the current action in the Google setup wizard.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;

/**
 * Makes the next required setup action explicit.
 */
final class GoogleWizardProgressView {
	/**
	 * Render the setup progress and next action.
	 *
	 * @param GoogleSettings                                          $settings     Current Google settings.
	 * @param string                                                  $status       Connection status.
	 * @param bool                                                    $secret_saved Whether a client secret is already stored.
	 * @param array<int, array{site_url: string, permission: string}> $properties   Cached properties.
	 * @return void
	 */
	public function render( GoogleSettings $settings, string $status, bool $secret_saved, array $properties ): void {
		$has_credentials = '' !== $settings->client_id() && $secret_saved;
		$connected       = GoogleTokenStore::STATUS_CONNECTED === $status;
		$has_property    = '' !== $settings->search_console_site_url();

		if ( ! $has_credentials ) {
			$title   = __( 'Next: add your Google credentials', 'cannyforge-archive' );
			$message = __( 'Upload the OAuth JSON file, then click Save credentials and continue below.', 'cannyforge-archive' );
		} elseif ( ! $connected ) {
			$title   = __( 'Next: connect your Google account', 'cannyforge-archive' );
			$message = __( 'Click Connect Google below and approve read-only access in Google.', 'cannyforge-archive' );
		} elseif ( ! $has_property && array() === $properties ) {
			$title   = __( 'Next: load your Search Console properties', 'cannyforge-archive' );
			$message = __( 'Click Load properties below, then choose the site you want the archive to use.', 'cannyforge-archive' );
		} elseif ( ! $has_property ) {
			$title   = __( 'Next: choose a Search Console property', 'cannyforge-archive' );
			$message = __( 'Choose a property from the dropdown, then click Save property and continue.', 'cannyforge-archive' );
		} else {
			$title   = __( 'Next: refresh Search Console data', 'cannyforge-archive' );
			$message = __( 'Your connection and property are ready. Refresh Search Console below to populate the archive.', 'cannyforge-archive' );
		}

		echo '<div class="cannyforge-google-wizard__progress" role="status">';
		echo '<span class="cannyforge-google-wizard__progress-kicker">' . esc_html__( 'Setup progress', 'cannyforge-archive' ) . '</span>';
		echo '<strong>' . esc_html( $title ) . '</strong>';
		echo '<span>' . esc_html( $message ) . '</span>';
		echo '</div>';
	}
}
