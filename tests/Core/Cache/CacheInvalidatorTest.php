<?php
/**
 * Tests for the cache invalidator.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Cache;

use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Core\Cache\CacheInvalidator;
use CannyForge\Archive\Core\Cache\SearchResultCache;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
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
		OptionStore::reset();
	}

	public function test_register_wires_hooks(): void {
		$invalidator = new CacheInvalidator( new ArchiveCache() );
		$invalidator->register();

		$this->assertTrue( HookSpy::has( 'save_post' ) );
		$this->assertTrue( HookSpy::has( 'deleted_post' ) );
		$this->assertTrue( HookSpy::has( 'added_post_meta' ) );
		$this->assertTrue( HookSpy::has( 'updated_post_meta' ) );
		$this->assertTrue( HookSpy::has( 'deleted_post_meta' ) );
		$this->assertTrue( HookSpy::has( 'set_object_terms' ) );
		$this->assertTrue( HookSpy::has( 'cannyforge_archive_settings_saved' ) );
		$this->assertTrue( HookSpy::has( 'created_term' ) );
		$this->assertTrue( HookSpy::has( 'edited_term' ) );
		$this->assertTrue( HookSpy::has( 'delete_term' ) );
		$this->assertTrue( HookSpy::has( 'profile_update' ) );
		$this->assertTrue( HookSpy::has( 'deleted_user' ) );
	}

	public function test_invalidate_clears_cache(): void {
		$cache       = new ArchiveCache();
		$invalidator = new CacheInvalidator( $cache );

		$settings = new Settings( mode: Mode::Blog );
		$cache->set( $settings, '<nav>cached</nav>' );
		$cache->set_page_one_post_ids( $settings, array( 17 ) );
		$this->assertSame( '<nav>cached</nav>', $cache->get( $settings ) );

		$invalidator->invalidate();

		$this->assertFalse( $cache->get( $settings ) );
		$this->assertFalse( $cache->get_page_one_post_ids( $settings ) );
	}

	/**
	 * Ticket 608: invalidate() also clears the whole-database search response
	 * cache — a stale search result would otherwise keep serving the same
	 * HTML fragment (with the same category/tag/author labels) that
	 * {@see test_invalidate_clears_cache()} already proves gets cleared for
	 * the archive page's own cache.
	 *
	 * @return void
	 */
	public function test_invalidate_clears_search_cache(): void {
		$search_cache = new SearchResultCache();
		$invalidator  = new CacheInvalidator( new ArchiveCache(), $search_cache );

		$query = new ContentQuery( 'crawl budget' );
		$search_cache->set( $query, array( 'html' => '<nav>cached search</nav>' ) );
		$this->assertSame( array( 'html' => '<nav>cached search</nav>' ), $search_cache->get( $query ) );

		$invalidator->invalidate();

		$this->assertFalse( $search_cache->get( $query ) );
	}

	/**
	 * A `save_post` event — the same hook already proven to clear the HTML
	 * cache — clears the search cache too, since both are invalidated by the
	 * same `invalidate()` call.
	 *
	 * @return void
	 */
	public function test_save_post_hook_callback_clears_search_cache_too(): void {
		$search_cache = new SearchResultCache();
		$invalidator  = new CacheInvalidator( new ArchiveCache(), $search_cache );
		$invalidator->register();

		$query = new ContentQuery( 'crawl budget' );
		$search_cache->set( $query, array( 'html' => '<nav>cached search</nav>' ) );

		$callback = HookSpy::first( 'save_post' );
		$this->assertNotNull( $callback );
		$callback();

		$this->assertFalse( $search_cache->get( $query ) );
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

	/**
	 * Meta and term-assignment mutations can change noindex and selection
	 * membership without saving the post object, so both clear page-one IDs.
	 *
	 * @return void
	 */
	public function test_meta_and_term_assignment_hooks_clear_page_one_membership(): void {
		foreach ( array( 'updated_post_meta', 'set_object_terms' ) as $hook ) {
			$cache       = new ArchiveCache();
			$invalidator = new CacheInvalidator( $cache );
			HookSpy::reset();
			$invalidator->register();

			$settings = new Settings( mode: Mode::Blog );
			$cache->set_page_one_post_ids( $settings, array( 17 ) );

			$callback = HookSpy::first( $hook );
			$this->assertNotNull( $callback, "Expected a callback registered for {$hook}" );
			$callback();

			$this->assertFalse( $cache->get_page_one_post_ids( $settings ), "Expected {$hook} to clear page-one membership." );
		}
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

	/**
	 * Term lifecycle hooks (create/edit/delete) clear the cache — the cached
	 * HTML embeds the whole-database category/tag filter-control options, so a
	 * term rename or deletion must not leave stale labels cached.
	 *
	 * @return void
	 */
	public function test_term_hook_callbacks_clear_cache(): void {
		foreach ( array( 'created_term', 'edited_term', 'delete_term' ) as $hook ) {
			$cache       = new ArchiveCache();
			$invalidator = new CacheInvalidator( $cache );
			HookSpy::reset();
			$invalidator->register();

			$settings = new Settings( mode: Mode::Blog );
			$cache->set( $settings, '<nav>blog</nav>' );

			$callback = HookSpy::first( $hook );
			$this->assertNotNull( $callback, "Expected a callback registered for {$hook}" );
			$callback();

			$this->assertFalse( $cache->get( $settings ), "Expected {$hook} to clear the cache" );
		}
	}

	/**
	 * User profile hooks (edit/delete) clear the cache — the cached HTML
	 * embeds the whole-database author filter-control options (display
	 * names), so a profile edit or account deletion must not leave stale
	 * entries cached.
	 *
	 * @return void
	 */
	public function test_user_hook_callbacks_clear_cache(): void {
		foreach ( array( 'profile_update', 'deleted_user' ) as $hook ) {
			$cache       = new ArchiveCache();
			$invalidator = new CacheInvalidator( $cache );
			HookSpy::reset();
			$invalidator->register();

			$settings = new Settings( mode: Mode::News );
			$cache->set( $settings, '<nav>news</nav>' );

			$callback = HookSpy::first( $hook );
			$this->assertNotNull( $callback, "Expected a callback registered for {$hook}" );
			$callback();

			$this->assertFalse( $cache->get( $settings ), "Expected {$hook} to clear the cache" );
		}
	}
}
