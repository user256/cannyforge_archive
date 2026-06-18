<?php
/**
 * Tests for the front-end archive asset enqueueing.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Frontend\ArchiveAssets;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Tests\HookSpy;
use PHPUnit\Framework\TestCase;

/**
 * Assets enqueue only on the archive request, never globally.
 */
class ArchiveAssetsTest extends TestCase {
	/**
	 * Reset recorded hooks and the query global before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		// Seeding the query global is the whole point of the test harness.
		$GLOBALS['wp_query'] = (object) array( 'query_vars' => array() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Build an assets controller with representative URL/version.
	 *
	 * @return ArchiveAssets
	 */
	private function assets(): ArchiveAssets {
		return new ArchiveAssets( 'http://example.test/wp-content/plugins/cannyforge-archive/', '0.1.0' );
	}

	/**
	 * Mark the current request as the archive endpoint.
	 *
	 * @return void
	 */
	private function onArchive(): void {
		// Seeding the query global is the whole point of the test harness.
		$GLOBALS['wp_query'] = (object) array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'query_vars' => array( ArchivePage::QUERY_VAR => '' ),
		);
	}

	/**
	 * Registration wires the front-end enqueue hook.
	 *
	 * @return void
	 */
	public function test_register_wires_enqueue_hook(): void {
		$this->assets()->register();

		$this->assertTrue( HookSpy::has( 'wp_enqueue_scripts' ) );
	}

	/**
	 * On the archive request, both the style and the script are enqueued.
	 *
	 * @return void
	 */
	public function test_enqueues_on_archive_request(): void {
		$this->onArchive();

		$this->assets()->enqueue();

		$this->assertTrue( HookSpy::has( 'style:' . ArchiveAssets::STYLE_HANDLE ) );
		$this->assertTrue( HookSpy::has( 'script:' . ArchiveAssets::SCRIPT_HANDLE ) );
	}

	/**
	 * Off the archive request, nothing is enqueued (no global asset bloat).
	 *
	 * @return void
	 */
	public function test_does_not_enqueue_off_archive(): void {
		$this->assets()->enqueue();

		$this->assertFalse( HookSpy::has( 'style:' . ArchiveAssets::STYLE_HANDLE ) );
		$this->assertFalse( HookSpy::has( 'script:' . ArchiveAssets::SCRIPT_HANDLE ) );
	}
}
