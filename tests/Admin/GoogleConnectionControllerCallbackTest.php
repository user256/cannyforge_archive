<?php
/**
 * Tests for the Google connection controller's `handle_callback()` flow.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\WpDieException;

/**
 * The callback's capability gate, CSRF-state ordering (ticket 614), and
 * user-facing failure notices — see {@see GoogleConnectionControllerTestCase}
 * for the shared fixture.
 */
class GoogleConnectionControllerCallbackTest extends GoogleConnectionControllerTestCase {
	/**
	 * `handle_callback()` refuses to run for a user lacking the required
	 * capability, before it ever touches the CSRF state transient or token
	 * status.
	 *
	 * @return void
	 */
	public function test_callback_refused_without_capability(): void {
		$tokens                                      = new GoogleTokenStore();
		$controller                                  = $this->controller_with_tokens( $tokens );
		$GLOBALS['cannyforge_test_current_user_can'] = false;

		set_transient( 'cannyforge_archive_google_oauth_good-state', 1 );
		$_GET = array(
			'code'  => 'auth-code',
			'state' => 'good-state',
		);

		$this->expectException( WpDieException::class );

		try {
			$controller->handle_callback();
		} finally {
			$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
			// The state transient is untouched: the capability gate runs first.
			$this->assertSame( 1, get_transient( 'cannyforge_archive_google_oauth_good-state' ) );
		}
	}

	// -- Callback CSRF-state ordering (AC2) -------------------------------------

	/**
	 * A callback carrying `error` but no CSRF state must not mutate token
	 * status: state is validated before the error branch runs.
	 *
	 * @return void
	 */
	public function test_callback_error_without_state_dies_and_does_not_mutate_status(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		$_GET = array( 'error' => 'access_denied' );

		$this->expectException( WpDieException::class );

		try {
			$controller->handle_callback();
		} finally {
			$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
		}
	}

	/**
	 * A callback whose state transient belongs to a different user is
	 * rejected, and status is not mutated.
	 *
	 * @return void
	 */
	public function test_callback_rejects_foreign_state_and_does_not_mutate_status(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		set_transient( 'cannyforge_archive_google_oauth_foreign-state', 999 );
		$GLOBALS['cannyforge_test_current_user_id'] = 1;
		$_GET                                       = array(
			'error' => 'access_denied',
			'state' => 'foreign-state',
		);

		$this->expectException( WpDieException::class );

		try {
			$controller->handle_callback();
		} finally {
			$this->assertSame( GoogleTokenStore::STATUS_DISCONNECTED, $tokens->status() );
		}
	}

	/**
	 * A callback with no matching transient at all (missing/expired state) is
	 * rejected the same way as a foreign state.
	 *
	 * @return void
	 */
	public function test_callback_rejects_missing_or_expired_state(): void {
		$controller = $this->controller_with_tokens( new GoogleTokenStore() );

		$_GET = array(
			'code'  => 'auth-code',
			'state' => 'never-issued-state',
		);

		$this->expectException( WpDieException::class );
		$controller->handle_callback();
	}

	/**
	 * A valid state accompanying an `error` callback DOES mutate status to
	 * error and redirects — distinguishing a legitimate provider error from
	 * the CSRF-able path above.
	 *
	 * @return void
	 */
	public function test_callback_error_with_valid_state_sets_error_status(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		set_transient( 'cannyforge_archive_google_oauth_good-state', 1 );
		$_GET = array(
			'error' => 'access_denied',
			'state' => 'good-state',
		);

		$this->assert_redirects( static fn () => $controller->handle_callback() );

		$this->assertSame( GoogleTokenStore::STATUS_ERROR, $tokens->status() );
	}

	// -- Callback failure notices (ticket 602) ----------------------------------

	/**
	 * A callback with a valid state but no `code` (and no `error`) is
	 * rejected with an actionable notice and an error status, rather than
	 * silently doing nothing.
	 *
	 * @return void
	 */
	public function test_callback_missing_code_sets_error_status_and_notice(): void {
		$tokens     = new GoogleTokenStore();
		$controller = $this->controller_with_tokens( $tokens );

		set_transient( 'cannyforge_archive_google_oauth_good-state', 1 );
		$_GET = array( 'state' => 'good-state' );

		$location = $this->assert_redirects( static fn () => $controller->handle_callback() );

		$this->assertSame( GoogleTokenStore::STATUS_ERROR, $tokens->status() );
		$this->assertSame( GoogleConnectionController::NOTICE_ERROR, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertNotSame( '', $this->query_param( $location, 'cf_google_notice' ) );
	}

	/**
	 * A callback with a valid state and code, whose token exchange fails
	 * (no live Google in the test runtime), redirects back with an error
	 * notice rather than dying or leaving the user without feedback.
	 *
	 * @return void
	 */
	public function test_callback_token_exchange_failure_sets_error_notice(): void {
		$controller = $this->controller_with_settings( new GoogleSettings( 'client-id', 'client-secret' ) );

		set_transient( 'cannyforge_archive_google_oauth_good-state', 1 );
		$_GET = array(
			'code'  => 'auth-code',
			'state' => 'good-state',
		);

		$location = $this->assert_redirects( static fn () => $controller->handle_callback() );

		$this->assertSame( GoogleConnectionController::NOTICE_ERROR, $this->query_param( $location, 'cf_google_notice_type' ) );
		$this->assertNotSame( '', $this->query_param( $location, 'cf_google_notice' ) );
	}

	/**
	 * A state transient is consumed on first use; replaying the same state
	 * value on a second callback is rejected.
	 *
	 * @return void
	 */
	public function test_callback_state_cannot_be_replayed(): void {
		$controller = $this->controller_with_settings( new GoogleSettings( 'client-id', 'client-secret' ) );

		set_transient( 'cannyforge_archive_google_oauth_one-time-state', 1 );
		$_GET = array(
			'code'  => 'auth-code',
			'state' => 'one-time-state',
		);

		// First use: state is valid, consumed; the token exchange itself then
		// fails in the test runtime (no live Google), which redirects rather
		// than dying — either way, state has been consumed.
		$this->assert_redirects( static fn () => $controller->handle_callback() );

		// Second use of the exact same state must be rejected.
		$this->expectException( WpDieException::class );
		$controller->handle_callback();
	}
}
