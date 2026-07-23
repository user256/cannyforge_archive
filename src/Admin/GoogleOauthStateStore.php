<?php
/**
 * CSRF state transient for the Google OAuth connect flow.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and consumes the one-shot OAuth state transient.
 *
 * The payload carries the user id, wizard origin, and whether Analytics scope
 * was requested for the wizard's property picker. Each state is consumed
 * (deleted) on first use, so a replayed callback with the same state is rejected.
 */
final class GoogleOauthStateStore {
	/**
	 * CSRF state transient prefix.
	 */
	private const STATE_PREFIX = 'cannyforge_archive_google_oauth_';

	/**
	 * Transient lifetime in seconds.
	 */
	private const TTL = 600;

	/**
	 * Create and persist a fresh state value for the current user.
	 *
	 * @param bool $from_wizard       Whether the connect was launched from the wizard.
	 * @param bool $request_analytics    Whether the connect requests Analytics access.
	 * @param bool $request_search_console Whether the connect requests Search Console access.
	 * @return string The state value to send to Google.
	 */
	public function create( bool $from_wizard, bool $request_analytics = false, bool $request_search_console = true ): string {
		$payload = array(
			'uid'            => get_current_user_id(),
			'wizard'         => $from_wizard,
			'search_console' => $request_search_console,
		);
		if ( $request_analytics ) {
			$payload['analytics'] = true;
		}

		$state = wp_generate_password( 32, false, false );
		set_transient( self::STATE_PREFIX . $state, $payload, self::TTL );

		return $state;
	}

	/**
	 * Validate and consume a callback state, dying on an invalid one.
	 *
	 * A bare numeric payload (a pre-upgrade transient) is still accepted as a
	 * non-wizard connect.
	 *
	 * @param string $state Callback state.
	 * @return array{wizard: bool, analytics: bool, search_console: bool} State details.
	 */
	public function consume( string $state ): array {
		$payload = get_transient( self::STATE_PREFIX . $state );
		delete_transient( self::STATE_PREFIX . $state );

		$uid            = is_array( $payload ) ? ( $payload['uid'] ?? null ) : $payload;
		$wizard         = is_array( $payload ) && ! empty( $payload['wizard'] );
		$analytics      = is_array( $payload ) && ! empty( $payload['analytics'] );
		$search_console = ! is_array( $payload ) || ! array_key_exists( 'search_console', $payload ) || ! empty( $payload['search_console'] );

		if ( ! is_numeric( $uid ) || (int) get_current_user_id() !== (int) $uid ) {
			wp_die( esc_html__( 'Invalid OAuth state. Try connecting again.', 'cannyforge-archive' ) );
		}

		return array(
			'wizard'         => $wizard,
			'analytics'      => $analytics,
			'search_console' => $search_console,
		);
	}
}
