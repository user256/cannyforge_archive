<?php
/**
 * Tests for the archive cache.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Cache;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the archive cache.
 *
 * @package CannyForge\Archive
 */
class ArchiveCacheTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		TransientStore::reset();
	}

	public function test_get_returns_false_on_miss(): void {
		$cache    = new ArchiveCache();
		$settings = new Settings( mode: Mode::Blog );

		$this->assertFalse( $cache->get( $settings ) );
	}

	public function test_set_and_get_round_trip(): void {
		$cache    = new ArchiveCache();
		$settings = new Settings( mode: Mode::Blog );
		$html     = '<nav>cached</nav>';

		$cache->set( $settings, $html );

		$this->assertSame( $html, $cache->get( $settings ) );
	}

	public function test_blog_and_news_caches_are_independent(): void {
		$cache         = new ArchiveCache();
		$blog_settings = new Settings( mode: Mode::Blog );
		$news_settings = new Settings( mode: Mode::News );

		$cache->set( $blog_settings, '<nav>blog</nav>' );
		$cache->set( $news_settings, '<nav>news</nav>' );

		$this->assertSame( '<nav>blog</nav>', $cache->get( $blog_settings ) );
		$this->assertSame( '<nav>news</nav>', $cache->get( $news_settings ) );
	}

	public function test_clear_removes_all_caches(): void {
		$cache         = new ArchiveCache();
		$blog_settings = new Settings( mode: Mode::Blog );
		$news_settings = new Settings( mode: Mode::News );

		$cache->set( $blog_settings, 'blog-html' );
		$cache->set( $news_settings, 'news-html' );

		$cache->clear();

		$this->assertFalse( $cache->get( $blog_settings ) );
		$this->assertFalse( $cache->get( $news_settings ) );
	}
}
