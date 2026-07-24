<?php
/**
 * Tests for the front-end archive page wiring.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Filters;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\ArchiveUrlResolver;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Tests\FixtureEntryProvider;
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
		unset( $GLOBALS['wp_query'] );
		unset( $GLOBALS['cannyforge_test_safe_redirect_result'] );
		$GLOBALS['cannyforge_test_get_terms_args']      = array();
		$GLOBALS['cannyforge_test_get_users_args']      = array();
		$GLOBALS['cannyforge_test_get_posts_args']      = array();
		$GLOBALS['cannyforge_test_wpdb_get_col_result'] = array();
		$GLOBALS['cannyforge_test_object_cache']        = array();
		$GLOBALS['cannyforge_test_cache_last_changed']  = array( 'posts' => '1' );
		$GLOBALS['wpdb']->queries                       = array();
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
	 * Disabled dimensions do not issue whole-database option queries.
	 *
	 * @return void
	 */
	public function test_disabled_filter_dimensions_are_not_queried(): void {
		$page = $this->page();
		$ref  = new \ReflectionMethod( $page, 'filter_options' );
		$ref->setAccessible( true );

		$options = $ref->invoke(
			$page,
			new Settings( filters: new Filters( true, false, false, false, false ) )
		);

		$this->assertSame( array(), $options );
		$this->assertSame( array(), $GLOBALS['cannyforge_test_get_terms_args'] );
		$this->assertSame( array(), $GLOBALS['cannyforge_test_get_users_args'] );
		$this->assertSame( array(), $GLOBALS['wpdb']->queries );
	}

	/**
	 * Default options query categories, tags and months, but not authors.
	 *
	 * @return void
	 */
	public function test_default_filter_options_skip_disabled_author_dimension(): void {
		$page = $this->page();
		$ref  = new \ReflectionMethod( $page, 'filter_options' );
		$ref->setAccessible( true );
		$ref->invoke( $page, new Settings() );

		$this->assertCount( 2, $GLOBALS['cannyforge_test_get_terms_args'] );
		$this->assertSame( array(), $GLOBALS['cannyforge_test_get_users_args'] );
		$this->assertCount( 1, $GLOBALS['wpdb']->queries );
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

	/**
	 * With no explicit resolver, the page builds one seeded with its own slug —
	 * so the redirect destination and the endpoint it redirects a stray tail
	 * away from always agree.
	 *
	 * @return void
	 */
	public function test_default_resolver_uses_configured_slug(): void {
		$page = $this->page( 'stories' );

		$ref = new \ReflectionProperty( $page, 'url_resolver' );
		$ref->setAccessible( true );
		$resolver = $ref->getValue( $page );

		$this->assertInstanceOf( ArchiveUrlResolver::class, $resolver );
		$this->assertSame( 'http://example.test/stories/', $resolver->endpoint_url() );
	}

	/**
	 * Ticket 612: with the real resolver and no `archive_url` override
	 * configured (the default), the tail-redirect target resolves to the
	 * archive endpoint URL — never an empty string. This is what the
	 * pre-fix `maybe_render()` got wrong (it redirected straight to the
	 * possibly-empty `archive_url` setting). Exercised at the resolver
	 * boundary rather than through `maybe_render()`/`redirect_tail()`
	 * directly, since a resolved (non-empty) target ends in `exit`, which is
	 * impractical to run inside PHPUnit.
	 *
	 * @return void
	 */
	public function test_redirect_target_never_empty_for_default_settings(): void {
		$page = $this->page();

		$ref = new \ReflectionProperty( $page, 'url_resolver' );
		$ref->setAccessible( true );
		$resolver = $ref->getValue( $page );

		$this->assertSame(
			'http://example.test/archive/',
			$resolver->destination_url( new Settings( mode: Mode::Blog ) )
		);
	}

	/**
	 * When the resolver produces no target at all, the endpoint fails closed
	 * to a 404 instead of following an unconditional blank-page `exit` —
	 * ticket 612's "redirect failure is handled explicitly" guarantee. A real
	 * {@see ArchiveUrlResolver} can't actually return an empty destination (it
	 * always falls back to the endpoint URL), so this substitutes a resolver
	 * double to exercise the defensive branch directly.
	 *
	 * @return void
	 */
	public function test_non_empty_tail_falls_back_to_404_when_resolver_yields_no_target(): void {
		$resolver = new class() extends ArchiveUrlResolver {
			public function destination_url( Settings $settings ): string {
				unset( $settings );
				return '';
			}
		};

		$page = new ArchivePage(
			new OptionsSettingsRepository(),
			new FixtureEntryProvider(),
			new ArchiveRenderer(),
			ArchivePage::DEFAULT_SLUG,
			null,
			null,
			$resolver
		);

		$GLOBALS['wp_query'] = (object) array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'query_vars' => array( ArchivePage::QUERY_VAR => 'unwanted-tail' ),
		);

		$page->maybe_render();

		$this->assertTrue( HookSpy::has( 'status_header:404' ) );
		$this->assertFalse( HookSpy::has( 'wp_safe_redirect' ) );
	}

	/**
	 * A rejected configured destination falls back to the local endpoint and
	 * then returns a 404 if WordPress rejects that redirect too.
	 *
	 * @return void
	 */
	public function test_rejected_tail_redirect_uses_local_fallback_before_404(): void {
		$page = $this->page();
		$GLOBALS['cannyforge_test_safe_redirect_result'] = false;

		$ref = new \ReflectionMethod( $page, 'redirect_tail' );
		$ref->setAccessible( true );
		$ref->invoke( $page, new Settings( archive_url: 'https://external.example/archive/' ) );

		$redirects = array_map(
			static fn ( callable $callback ): array => $callback(),
			HookSpy::callbacks_for( 'wp_safe_redirect' )
		);

		$this->assertSame(
			array(
				array( 'https://external.example/archive/', 301 ),
				array( 'http://example.test/archive/', 301 ),
			),
			$redirects
		);
		$this->assertTrue( HookSpy::has( 'status_header:404' ) );
	}

	/**
	 * With full-archive pagination on, page-one HTML is fragment-cached
	 * (ticket 731).
	 *
	 * @return void
	 */
	public function test_full_archive_page_one_uses_fragment_cache(): void {
		$cache        = new ArchiveCache();
		$continuation = new class() extends \CannyForge\Archive\Core\Archive\FullArchiveContinuationProvider {
			public function has_continuation( Settings $settings, array $excluded_ids ): bool {
				unset( $settings, $excluded_ids );
				return true;
			}
		};
		$page         = new ArchivePage(
			new OptionsSettingsRepository(),
			new FixtureEntryProvider(),
			new ArchiveRenderer(),
			ArchivePage::DEFAULT_SLUG,
			$cache,
			null,
			null,
			$continuation
		);
		$settings     = new Settings( mode: Mode::Blog, full_archive_pagination: true );
		$ref          = new \ReflectionMethod( $page, 'build_html' );
		$ref->setAccessible( true );

		$html = $ref->invoke( $page, $settings );
		$this->assertStringContainsString( 'Browse the full archive', $html );
		$this->assertSame( $html, $cache->get( $settings ) );

		// A second build must be a cache hit: the stub would still return the
		// same CTA, but provider/render hooks must not fire again.
		HookSpy::reset();
		$again = $ref->invoke( $page, $settings );
		$this->assertSame( $html, $again );
		$this->assertFalse( HookSpy::has( 'do_action:cannyforge_archive_before_render' ) );
	}
}
