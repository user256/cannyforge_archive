<?php
/**
 * Tests for the root uninstall.php script (ticket 606).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

use CannyForge\Archive\Bootstrap\UninstallCleaner;
use PHPUnit\Framework\TestCase;

/**
 * Exercises uninstall.php itself (not just the UninstallCleaner class it
 * delegates to), so the WP_UNINSTALL_PLUGIN guard, the multisite iteration,
 * and the direct-query OAuth state transient cleanup are covered end to end.
 *
 * The script has no function/class declarations of its own outside
 * function_exists() guards, so re-`require`-ing it per test is safe and
 * re-runs its top-level cleanup logic fresh each time.
 */
class UninstallScriptTest extends TestCase {
	/**
	 * Absolute path to the script under test.
	 *
	 * @var string
	 */
	private const SCRIPT_RELATIVE = '/../uninstall.php';

	/**
	 * Reset every piece of shared in-memory state the script touches.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		TransientStore::reset();
		unset(
			$GLOBALS['cannyforge_test_is_multisite'],
			$GLOBALS['cannyforge_test_site_ids'],
			$GLOBALS['cannyforge_test_current_blog_id'],
			$GLOBALS['cannyforge_test_switch_to_blog_calls'],
			$GLOBALS['cannyforge_test_restore_current_blog_calls']
		);

		if ( isset( $GLOBALS['wpdb'] ) && $GLOBALS['wpdb'] instanceof \wpdb ) {
			$GLOBALS['wpdb']->queries = array();
			$GLOBALS['wpdb']->options = 'wp_options';
		}

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}
	}

	/**
	 * A single-site uninstall deletes every known option and fixed-name
	 * transient, and issues the direct-query cleanup for dynamically suffixed
	 * OAuth state and Google property-list transients.
	 *
	 * @return void
	 */
	public function test_uninstall_cleans_up_a_single_site(): void {
		foreach ( UninstallCleaner::option_keys() as $key ) {
			OptionStore::set( $key, 'seeded' );
		}

		foreach ( UninstallCleaner::transient_keys() as $key ) {
			TransientStore::set( $key, '<p>seeded html</p>' );
		}
		TransientStore::set( 'cannyforge_archive_google_oauth_state-1', 'oauth state' );
		TransientStore::set( 'cannyforge_archive_sc_properties_12', array( 'sc' ) );
		TransientStore::set( 'cannyforge_archive_sc_properties_34', array( 'sc' ) );
		TransientStore::set( 'cannyforge_archive_ga4_properties_12', array( 'ga4' ) );
		TransientStore::set( 'cannyforge_archive_ga4_properties_34', array( 'ga4' ) );
		TransientStore::set( 'unrelated_transient', 'keep me' );

		require __DIR__ . self::SCRIPT_RELATIVE;

		foreach ( UninstallCleaner::option_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, OptionStore::all(), "Option {$key} survived uninstall." );
		}

		foreach ( UninstallCleaner::transient_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, TransientStore::all(), "Transient {$key} survived uninstall." );
		}
		$this->assertArrayNotHasKey( 'cannyforge_archive_google_oauth_state-1', TransientStore::all() );
		$this->assertArrayNotHasKey( 'cannyforge_archive_sc_properties_12', TransientStore::all() );
		$this->assertArrayNotHasKey( 'cannyforge_archive_sc_properties_34', TransientStore::all() );
		$this->assertArrayNotHasKey( 'cannyforge_archive_ga4_properties_12', TransientStore::all() );
		$this->assertArrayNotHasKey( 'cannyforge_archive_ga4_properties_34', TransientStore::all() );
		$this->assertSame( 'keep me', TransientStore::all()['unrelated_transient'] ?? null );

		$this->assertCount( 1, $GLOBALS['wpdb']->queries, 'Expected exactly one direct-query Google dynamic transient cleanup.' );

		// esc_like() + prepare() backslash-escape underscores (a single-char
		// SQL wildcard) for correct SQL syntax; strip backslashes so the
		// assertion doesn't depend on exactly how many accumulate.
		$query = str_replace( '\\', '', $GLOBALS['wpdb']->queries[0] );
		$this->assertStringContainsString( '_transient_cannyforge_archive_google_oauth_', $query );
		$this->assertStringContainsString( '_transient_timeout_cannyforge_archive_google_oauth_', $query );
		$this->assertStringContainsString( '_transient_cannyforge_archive_sc_properties_', $query );
		$this->assertStringContainsString( '_transient_timeout_cannyforge_archive_sc_properties_', $query );
		$this->assertStringContainsString( '_transient_cannyforge_archive_ga4_properties_', $query );
		$this->assertStringContainsString( '_transient_timeout_cannyforge_archive_ga4_properties_', $query );
		$this->assertStringContainsString( 'DELETE FROM `wp_options`', $query );
		$this->assertStringNotContainsString( '{options_table}', $query );

		$this->assertArrayNotHasKey( 'cannyforge_test_switch_to_blog_calls', $GLOBALS, 'Single-site uninstall must not iterate sites.' );
	}

	/**
	 * A multisite uninstall switches to every site, cleans each one up, and
	 * always restores the current blog afterwards.
	 *
	 * @return void
	 */
	public function test_uninstall_iterates_every_site_on_multisite(): void {
		$GLOBALS['cannyforge_test_is_multisite'] = true;
		$GLOBALS['cannyforge_test_site_ids']     = array( 1, 2, 3 );

		foreach ( UninstallCleaner::option_keys() as $key ) {
			OptionStore::set( $key, 'seeded' );
		}

		require __DIR__ . self::SCRIPT_RELATIVE;

		$this->assertSame( array( 1, 2, 3 ), $GLOBALS['cannyforge_test_switch_to_blog_calls'] );
		$this->assertSame( 3, $GLOBALS['cannyforge_test_restore_current_blog_calls'] );
		$this->assertArrayNotHasKey( 'cannyforge_test_current_blog_id', $GLOBALS, 'restore_current_blog() must run after every site.' );

		// One direct-query OAuth cleanup per site.
		$this->assertCount( 3, $GLOBALS['wpdb']->queries );

		foreach ( UninstallCleaner::option_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, OptionStore::all(), "Option {$key} survived multisite uninstall." );
		}
	}

	/**
	 * Requiring the script without WP_UNINSTALL_PLUGIN defined must not run
	 * any cleanup — it is a fatal-safe no-op guard. Run out of process since
	 * the guard calls exit().
	 *
	 * @return void
	 */
	public function test_script_exits_harmlessly_without_the_uninstall_constant(): void {
		$script = realpath( __DIR__ . self::SCRIPT_RELATIVE );
		$this->assertNotFalse( $script, 'uninstall.php not found.' );

		$output      = array();
		$result_code = 0;
		$php_snippet = "require '" . addcslashes( $script, "'\\" ) . "';";
		exec( 'php -r ' . escapeshellarg( $php_snippet ) . ' 2>&1', $output, $result_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- test-only: confirms the WP_UNINSTALL_PLUGIN guard exits harmlessly without a WordPress runtime.

		$this->assertSame( 0, $result_code, "Requiring uninstall.php without WP_UNINSTALL_PLUGIN defined should exit cleanly:\n" . implode( "\n", $output ) );
		$this->assertSame( array(), $output, 'The guard should produce no output.' );
	}
}
