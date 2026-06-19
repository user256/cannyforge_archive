<?php
/**
 * Tests for the cache invalidator.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Cache;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Core\Cache\CacheInvalidator;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the cache invalidator.
 *
 * @package CannyForge\Archive
 */
class CacheInvalidatorTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		TransientStore::reset();
	}

	public function test_register_wires_hooks(): void {
		$invalidator = new CacheInvalidator( new ArchiveCache() );
		$invalidator->register();

		$this->assertTrue( HookSpy::has( 'save_post' ) );
		$this->assertTrue( HookSpy::has( 'deleted_post' ) );
		$this->assertTrue( HookSpy::has( 'cannyforge_archive_settings_saved' ) );
	}

	public function test_invalidate_clears_cache(): void {
		$cache       = new ArchiveCache();
		$invalidator = new CacheInvalidator( $cache );

		$settings = new Settings( mode: Mode::Blog );
		$cache->set( $settings, '<nav>cached</nav>' );
		$this->assertSame( '<nav>cached</nav>', $cache->get( $settings ) );

		$invalidator->invalidate();

		$this->assertFalse( $cache->get( $settings ) );
	}

	public function test_save_post_hook_callback_clears_cache(): void {
		$cache       = new ArchiveCache();
		$invalidator = new CacheInvalidator( $cache );
		$invalidator->register();

		$settings = new Settings( mode: Mode::News );
		$cache->set( $settings, '<nav>news</nav>' );

		$callback = HookSpy::first( 'save_post' );
		$this->assertNotNull( $callback );
		$callback();

		$this->assertFalse( $cache->get( $settings ) );
	}

	public function test_deleted_post_hook_callback_clears_cache(): void {
		$cache       = new ArchiveCache();
		$invalidator = new CacheInvalidator( $cache );
		$invalidator->register();

		$settings = new Settings( mode: Mode::Blog );
		$cache->set( $settings, '<nav>blog</nav>' );

		$callback = HookSpy::first( 'deleted_post' );
		$this->assertNotNull( $callback );
		$callback();

		$this->assertFalse( $cache->get( $settings ) );
	}

	public function test_settings_saved_hook_callback_clears_cache(): void {
		$cache       = new ArchiveCache();
		$invalidator = new CacheInvalidator( $cache );
		$invalidator->register();

		$settings = new Settings( mode: Mode::Blog );
		$cache->set( $settings, '<nav>blog</nav>' );

		$callback = HookSpy::first( 'cannyforge_archive_settings_saved' );
		$this->assertNotNull( $callback );
		$callback();

		$this->assertFalse( $cache->get( $settings ) );
	}
}
