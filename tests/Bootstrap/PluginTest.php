<?php
/**
 * Test for the Plugin composition root.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use CannyForge\Archive\Bootstrap\Plugin;
use CannyForge\Archive\Tests\HookSpy;

/**
 * Test the composition root.
 */
class PluginTest extends TestCase {
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
	 * The composition root constructs and initialises without error.
	 *
	 * @return void
	 */
	public function test_init(): void {
		$plugin = new Plugin();
		$plugin->init();
		$this->assertInstanceOf( Plugin::class, $plugin );
	}

	/**
	 * Booting registers the admin-menu hook (the admin page wiring).
	 *
	 * @return void
	 */
	public function test_init_registers_admin_menu(): void {
		( new Plugin() )->init();
		$this->assertTrue( HookSpy::has( 'admin_menu' ) );
	}
}
