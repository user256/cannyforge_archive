<?php
/**
 * Tests for the admin stylesheet enqueueing.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\AdminAssets;
use CannyForge\Archive\Admin\SettingsPage;
use CannyForge\Archive\Tests\HookSpy;
use PHPUnit\Framework\TestCase;

/**
 * The admin stylesheet enqueues only on the plugin's settings page.
 */
class AdminAssetsTest extends TestCase {
	/**
	 * Reset recorded hooks before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
	}

	/**
	 * Build an assets controller with representative URL/version.
	 *
	 * @return AdminAssets
	 */
	private function assets(): AdminAssets {
		return new AdminAssets( 'http://example.test/wp-content/plugins/cannyforge-archive/', '0.1.0' );
	}

	/**
	 * Registration wires the admin enqueue hook.
	 *
	 * @return void
	 */
	public function test_register_wires_enqueue_hook(): void {
		$this->assets()->register();

		$this->assertTrue( HookSpy::has( 'admin_enqueue_scripts' ) );
	}

	/**
	 * On the plugin's page hook, the stylesheet is enqueued.
	 *
	 * @return void
	 */
	public function test_enqueues_on_plugin_page(): void {
		$this->assets()->enqueue( 'toplevel_page_' . SettingsPage::PAGE_SLUG );

		$this->assertTrue( HookSpy::has( 'style:' . AdminAssets::STYLE_HANDLE ) );
		$this->assertTrue( HookSpy::has( 'script:' . AdminAssets::SCRIPT_HANDLE ) );
	}

	/**
	 * On any other admin page, nothing is enqueued.
	 *
	 * @return void
	 */
	public function test_does_not_enqueue_on_other_pages(): void {
		$this->assets()->enqueue( 'edit.php' );

		$this->assertFalse( HookSpy::has( 'style:' . AdminAssets::STYLE_HANDLE ) );
	}
}
