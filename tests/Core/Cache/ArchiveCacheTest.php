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

	/**
	 * Page-one local membership is cached independently of its rendered HTML.
	 *
	 * @return void
	 */
	public function test_page_one_post_ids_round_trip_as_clean_stable_ids(): void {
		$cache    = new ArchiveCache();
		$settings = new Settings( mode: Mode::Blog );

		$this->assertFalse( $cache->get_page_one_post_ids( $settings ) );
		$cache->set_page_one_post_ids( $settings, array( 0, 17, 17, -5, 23 ) );

		$this->assertSame( array( 17, 5, 23 ), $cache->get_page_one_post_ids( $settings ) );
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

	/**
	 * Every {@see Mode} case, including Hybrid, caches independently — driven
	 * from the enum rather than a hand-maintained list of modes, so a future
	 * mode is covered automatically.
	 *
	 * @return void
	 */
	public function test_every_mode_caches_independently(): void {
		$cache = new ArchiveCache();

		foreach ( Mode::cases() as $mode ) {
			$cache->set( new Settings( mode: $mode ), '<nav>' . $mode->value . '</nav>' );
		}

		foreach ( Mode::cases() as $mode ) {
			$this->assertSame( '<nav>' . $mode->value . '</nav>', $cache->get( new Settings( mode: $mode ) ) );
		}
	}

	public function test_clear_removes_all_caches(): void {
		$cache = new ArchiveCache();

		$settings_by_mode = array();
		foreach ( Mode::cases() as $mode ) {
			$settings_by_mode[ $mode->value ] = new Settings( mode: $mode );
			$cache->set( $settings_by_mode[ $mode->value ], $mode->value . '-html' );
		}

		$cache->clear();

		foreach ( $settings_by_mode as $settings ) {
			$this->assertFalse( $cache->get( $settings ) );
		}
	}

	/**
	 * Hybrid mode specifically — the case ticket 612 found untested — is
	 * cleared, not just Blog and News.
	 *
	 * @return void
	 */
	public function test_clear_removes_hybrid_cache(): void {
		$cache           = new ArchiveCache();
		$hybrid_settings = new Settings( mode: Mode::Hybrid );

		$cache->set( $hybrid_settings, '<nav>hybrid</nav>' );
		$cache->set_page_one_post_ids( $hybrid_settings, array( 17 ) );
		$this->assertSame( '<nav>hybrid</nav>', $cache->get( $hybrid_settings ) );

		$cache->clear();

		$this->assertFalse( $cache->get( $hybrid_settings ) );
		$this->assertFalse( $cache->get_page_one_post_ids( $hybrid_settings ) );
	}

	/**
	 * Full-archive page-one HTML does not share a key with the compact page
	 * (ticket 731).
	 *
	 * @return void
	 */
	public function test_full_archive_html_cache_is_independent_of_compact_cache(): void {
		$cache   = new ArchiveCache();
		$compact = new Settings( mode: Mode::Blog, full_archive_pagination: false );
		$full    = new Settings( mode: Mode::Blog, full_archive_pagination: true );

		$cache->set( $compact, '<nav>compact</nav>' );
		$cache->set( $full, '<nav>full</nav>' );

		$this->assertSame( '<nav>compact</nav>', $cache->get( $compact ) );
		$this->assertSame( '<nav>full</nav>', $cache->get( $full ) );

		$cache->clear();

		$this->assertFalse( $cache->get( $compact ) );
		$this->assertFalse( $cache->get( $full ) );
	}
}
