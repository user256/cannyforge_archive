<?php
/**
 * Regression test: deactivate -> reactivate must preserve settings
 * (ticket 606). The cleanup that deletes plugin data lives only in
 * uninstall.php; the deactivation hook in cannyforge-archive.php must never
 * touch stored options/transients.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Bootstrap;

use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the actual closures registered by cannyforge-archive.php via
 * register_activation_hook()/register_deactivation_hook(), rather than
 * re-describing their intent, so a future change that adds data cleanup to
 * deactivation would fail this test.
 */
class PluginLifecycleTest extends TestCase {
	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private const MAIN_FILE_RELATIVE = '/../../cannyforge-archive.php';

	/**
	 * Reset shared test state and re-register the plugin's lifecycle hooks
	 * fresh for every test (the main file has no function/class declarations,
	 * so re-`require`-ing it is safe and keeps this test isolated from
	 * whatever other test classes did with HookSpy).
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		HookSpy::reset();
		require $this->main_file();
	}

	/**
	 * The main plugin file's canonical absolute path — matches the
	 * `__FILE__` value cannyforge-archive.php itself passes to
	 * register_activation_hook()/register_deactivation_hook(), which PHP
	 * always resolves to a canonical path (no `..` segments).
	 *
	 * @return string
	 */
	private function main_file(): string {
		$resolved = realpath( __DIR__ . self::MAIN_FILE_RELATIVE );
		$this->assertNotFalse( $resolved, 'cannyforge-archive.php not found.' );

		return $resolved;
	}

	/**
	 * Deactivating the plugin must not delete or otherwise touch the stored
	 * settings option — only uninstall.php is allowed to do that.
	 *
	 * @return void
	 */
	public function test_deactivation_hook_preserves_settings(): void {
		$deactivation = HookSpy::first( 'deactivation:' . $this->main_file() );
		$this->assertIsCallable( $deactivation, 'No deactivation hook was registered by cannyforge-archive.php.' );

		OptionStore::set( 'cannyforge_archive_settings', array( 'mode' => 'news' ) );
		OptionStore::set( 'cannyforge_archive_google_refresh_token', 'encrypted-token' );

		$deactivation();

		$this->assertSame(
			array( 'mode' => 'news' ),
			OptionStore::all()['cannyforge_archive_settings'] ?? null,
			'Deactivation must never delete or modify the settings option.'
		);
		$this->assertSame(
			'encrypted-token',
			OptionStore::all()['cannyforge_archive_google_refresh_token'] ?? null,
			'Deactivation must never touch the stored Google token.'
		);
	}

	/**
	 * Reactivating afterwards (activation hook) must also leave the
	 * previously stored settings intact — "deactivate then reactivate"
	 * round-trips the data untouched.
	 *
	 * @return void
	 */
	public function test_activation_hook_after_deactivation_preserves_settings(): void {
		$main_file    = $this->main_file();
		$deactivation = HookSpy::first( 'deactivation:' . $main_file );
		$activation   = HookSpy::first( 'activation:' . $main_file );
		$this->assertIsCallable( $deactivation, 'No deactivation hook was registered by cannyforge-archive.php.' );
		$this->assertIsCallable( $activation, 'No activation hook was registered by cannyforge-archive.php.' );

		OptionStore::set( 'cannyforge_archive_settings', array( 'mode' => 'blog' ) );

		$deactivation();
		$activation();

		$this->assertSame(
			array( 'mode' => 'blog' ),
			OptionStore::all()['cannyforge_archive_settings'] ?? null,
			'Deactivate -> reactivate must round-trip the stored settings untouched.'
		);
	}
}
