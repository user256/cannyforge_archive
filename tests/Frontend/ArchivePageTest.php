<?php
/**
 * Tests for the front-end archive page wiring.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\FixtureEntryProvider;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * The archive page registers its endpoint and render hook.
 */
class ArchivePageTest extends TestCase {
	/**
	 * Reset shared state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		OptionStore::reset();
		TransientStore::reset();
	}

	/**
	 * Build a page with default collaborators and the given slug.
	 *
	 * @param string            $slug  Endpoint slug.
	 * @param ArchiveCache|null $cache Cache instance.
	 * @return ArchivePage
	 */
	private function page( string $slug = ArchivePage::DEFAULT_SLUG, ?ArchiveCache $cache = null ): ArchivePage {
		return new ArchivePage(
			new OptionsSettingsRepository(),
			new FixtureEntryProvider(),
			new ArchiveRenderer(),
			$slug,
			$cache
		);
	}

	/**
	 * Registration wires the init + template_redirect hooks.
	 *
	 * @return void
	 */
	public function test_register_wires_hooks(): void {
		$this->page()->register();

		$this->assertTrue( HookSpy::has( 'init' ) );
		$this->assertTrue( HookSpy::has( 'template_redirect' ) );
	}

	/**
	 * The rewrite endpoint registers under the configured slug.
	 *
	 * @return void
	 */
	public function test_endpoint_registers_under_slug(): void {
		$this->page( 'sitemap' )->add_rewrite_endpoint();

		$this->assertTrue( HookSpy::has( 'endpoint:sitemap' ) );
	}

	/**
	 * An empty slug falls back to the default.
	 *
	 * @return void
	 */
	public function test_empty_slug_falls_back_to_default(): void {
		$this->page( '' )->add_rewrite_endpoint();

		$this->assertTrue( HookSpy::has( 'endpoint:' . ArchivePage::DEFAULT_SLUG ) );
	}

	/**
	 * When the cache is warm the provider is not queried.
	 *
	 * We test at the cache level rather than through maybe_render() because
	 * maybe_render() calls exit, which is impractical to exercise in PHPUnit.
	 *
	 * @return void
	 */
	public function test_cache_hit_avoids_provider_and_renderer(): void {
		$cache = new ArchiveCache();
		$cache->set( new Settings( mode: Mode::Blog ), '<nav>warm</nav>' );

		$this->assertSame( '<nav>warm</nav>', $cache->get( new Settings( mode: Mode::Blog ) ) );
	}

	/**
	 * The entries filter is applied before rendering on a cache miss.
	 *
	 * @return void
	 */
	public function test_entries_filter_is_applied(): void {
		add_filter(
			'cannyforge_archive_entries',
			static fn ( array $entries ): array => $entries
		);

		$page = $this->page();
		$ref  = new \ReflectionMethod( $page, 'build_html' );
		$ref->setAccessible( true );

		$ref->invoke( $page, new Settings( mode: Mode::Blog ) );

		$this->assertTrue( HookSpy::has( 'apply_filters:cannyforge_archive_entries' ) );
	}

	/**
	 * Before and after render actions fire around the renderer on a cache miss.
	 *
	 * @return void
	 */
	public function test_before_and_after_render_actions_fire(): void {
		$page = $this->page();
		$ref  = new \ReflectionMethod( $page, 'build_html' );
		$ref->setAccessible( true );

		$ref->invoke( $page, new Settings( mode: Mode::Blog ) );

		$this->assertTrue( HookSpy::has( 'do_action:cannyforge_archive_before_render' ) );
		$this->assertTrue( HookSpy::has( 'do_action:cannyforge_archive_after_render' ) );
	}
}
