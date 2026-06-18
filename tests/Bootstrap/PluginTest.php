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

/**
 * Test the composition root.
 */
class PluginTest extends TestCase {
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
}
