<?php
/**
 * Tests for archive SEO interoperability with third-party SEO plugins.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Core\Seo\HeadTagBuilder;
use CannyForge\Archive\Core\Seo\SeoProvider;
use CannyForge\Archive\Core\Seo\SeoProviderDetector;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Frontend\SeoHead;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * The archive never emits two canonical tags or contradictory robots
 * directives when a supported SEO plugin (Yoast SEO, Rank Math) is active
 * (ticket 615): this plugin's own head fragment is suppressed, and its
 * resolved values are fed into the provider's own public filters instead.
 *
 * Real WordPress with either plugin actually installed is not available to
 * this suite (ticket 603's integration harness); Yoast/Rank Math presence is
 * faked via the injectable {@see SeoProviderDetector}, and the interop
 * behaviour itself is exercised through the real `add_filter()` /
 * `apply_filters()` shim in {@see \CannyForge\Archive\Tests\HookSpy} — i.e.
 * these register the same hook names/signatures Yoast and Rank Math publicly
 * document, and assert against invoking them, not against a mock of this
 * plugin's own internals.
 */
class SeoHeadProviderInteropTest extends TestCase {
	/**
	 * Reset shared state and the query global before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		OptionStore::reset();
		// Seeding the query global is the whole point of the test harness.
		$GLOBALS['wp_query'] = (object) array( 'query_vars' => array() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Build a SEO head controller with the real repository and the given
	 * detector.
	 *
	 * @param SeoProviderDetector $detector Stand-in provider detector.
	 * @return SeoHead
	 */
	private function head( SeoProviderDetector $detector ): SeoHead {
		return new SeoHead(
			new OptionsSettingsRepository(),
			new HeadTagBuilder(),
			ArchivePage::DEFAULT_SLUG,
			null,
			$detector
		);
	}

	/**
	 * A detector that reports the given provider as active.
	 *
	 * @param SeoProvider $provider The provider to report.
	 * @return SeoProviderDetector
	 */
	private function detecting( SeoProvider $provider ): SeoProviderDetector {
		return new SeoProviderDetector(
			static fn (): bool => SeoProvider::Yoast === $provider,
			static fn (): bool => SeoProvider::RankMath === $provider
		);
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
	 * Registration wires every provider-bridge filter for both supported
	 * providers, regardless of whether either is actually active — harmless,
	 * since nothing calls `apply_filters()` on a hook name the installed
	 * plugin(s) never fire.
	 *
	 * @return void
	 */
	public function test_register_wires_provider_bridge_filters(): void {
		$this->head( $this->detecting( SeoProvider::None ) )->register();

		foreach (
			array(
				'filter:wpseo_title',
				'filter:wpseo_metadesc',
				'filter:wpseo_robots',
				'filter:wpseo_canonical',
				'filter:rank_math/frontend/title',
				'filter:rank_math/frontend/description',
				'filter:rank_math/frontend/robots',
				'filter:rank_math/frontend/canonical',
			) as $hook
		) {
			$this->assertTrue( HookSpy::has( $hook ), $hook . ' should be registered' );
		}
	}

	/**
	 * When a supported SEO plugin is active, this plugin yields the document
	 * title filter entirely — even with a title configured — because the
	 * provider now owns the title via its own filter bridge instead.
	 *
	 * @return void
	 */
	public function test_filter_title_yields_when_provider_active(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'title' => 'Story Archive' ) )
		);

		$head = $this->head( $this->detecting( SeoProvider::Yoast ) );

		$this->assertSame( 'Provider Title', $head->filter_title( 'Provider Title' ) );
	}

	/**
	 * When a supported SEO plugin is active, this plugin's own head fragment is
	 * suppressed — no robots/description/canonical tag of its own — so the
	 * archive never ends up with two canonical tags or contradictory robots.
	 *
	 * @return void
	 */
	public function test_own_fragment_suppressed_when_provider_active(): void {
		$this->onArchive();

		foreach ( array( SeoProvider::Yoast, SeoProvider::RankMath ) as $provider ) {
			$head = $this->head( $this->detecting( $provider ) );

			ob_start();
			$head->maybe_render();
			$out = (string) ob_get_clean();

			$this->assertSame( '', $out, $provider->value . ' active should suppress the own fragment' );
		}
	}

	/**
	 * The `cannyforge_archive_seo_head` escape hatch still fires even when a
	 * provider is active — against the (now empty) fragment this plugin would
	 * otherwise have emitted — so a site owner can still force output.
	 *
	 * @return void
	 */
	public function test_escape_hatch_still_applies_when_provider_active(): void {
		$this->onArchive();
		HookSpy::record(
			'filter:' . SeoHead::FILTER,
			static fn ( string $tags, SeoProvider $provider ): string => $tags . '<!-- ' . $provider->value . ' -->'
		);

		$head = $this->head( $this->detecting( SeoProvider::Yoast ) );

		ob_start();
		$head->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertSame( '<!-- yoast -->', $out );
	}

	/**
	 * Provider bridge: Yoast's `wpseo_title` filter receives the configured
	 * title on the archive, and the plugin's own title otherwise.
	 *
	 * @return void
	 */
	public function test_provider_bridge_title(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'title' => 'Story Archive' ) )
		);
		$this->head( $this->detecting( SeoProvider::Yoast ) )->register();

		$callback = HookSpy::first( 'filter:wpseo_title' );
		$this->assertNotNull( $callback );
		$this->assertSame( 'Story Archive', $callback( "Yoast's Own Title" ) );
	}

	/**
	 * Provider bridge: with no title configured, the provider's own title
	 * passes through unchanged (never falls back to a theme default here —
	 * the provider already resolved one).
	 *
	 * @return void
	 */
	public function test_provider_bridge_title_passthrough_when_unset(): void {
		$this->onArchive();
		$this->head( $this->detecting( SeoProvider::Yoast ) )->register();

		$callback = HookSpy::first( 'filter:wpseo_title' );
		$this->assertSame( "Yoast's Own Title", $callback( "Yoast's Own Title" ) );
	}

	/**
	 * Provider bridge: off the archive request, the provider's own title is
	 * left untouched.
	 *
	 * @return void
	 */
	public function test_provider_bridge_title_untouched_off_archive(): void {
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'title' => 'Story Archive' ) )
		);
		$this->head( $this->detecting( SeoProvider::Yoast ) )->register();

		$callback = HookSpy::first( 'filter:wpseo_title' );
		$this->assertSame( 'Other Page Title', $callback( 'Other Page Title' ) );
	}

	/**
	 * Provider bridge: Rank Math's `rank_math/frontend/description` filter
	 * receives the configured description on the archive.
	 *
	 * @return void
	 */
	public function test_provider_bridge_description(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'meta_description' => 'Every article we publish.' ) )
		);
		$this->head( $this->detecting( SeoProvider::RankMath ) )->register();

		$callback = HookSpy::first( 'filter:rank_math/frontend/description' );
		$this->assertSame( 'Every article we publish.', $callback( "Rank Math's Own Description" ) );
	}

	/**
	 * Provider bridge: Yoast's `wpseo_robots` filter always receives this
	 * plugin's robots directive — archive indexability is a CannyForge-owned
	 * setting, not the provider's to guess.
	 *
	 * @return void
	 */
	public function test_provider_bridge_robots_string(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'index' => false ) )
		);
		$this->head( $this->detecting( SeoProvider::Yoast ) )->register();

		$callback = HookSpy::first( 'filter:wpseo_robots' );
		$this->assertSame( 'noindex,follow', $callback( 'index,follow' ) );
	}

	/**
	 * Provider bridge: Rank Math's `rank_math/frontend/robots` filter receives
	 * the robots directive in Rank Math's array shape.
	 *
	 * @return void
	 */
	public function test_provider_bridge_robots_array(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array(
				'seo' => array(
					'index'  => true,
					'follow' => false,
				),
			)
		);
		$this->head( $this->detecting( SeoProvider::RankMath ) )->register();

		$callback = HookSpy::first( 'filter:rank_math/frontend/robots' );
		$this->assertSame( array( 'index', 'nofollow' ), $callback( array( 'index', 'follow' ) ) );
	}

	/**
	 * Provider bridge: the canonical filter always resolves through
	 * {@see \CannyForge\Archive\Core\Seo\CanonicalUrlResolver} — the same
	 * resolution as this plugin's own fragment — never the provider's guess,
	 * and never the pagination-link destination.
	 *
	 * @return void
	 */
	public function test_provider_bridge_canonical_ignores_pagination_destination(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'archive_url' => 'https://site.test/view-everything/' )
		);
		$this->head( $this->detecting( SeoProvider::Yoast ) )->register();

		$callback = HookSpy::first( 'filter:wpseo_canonical' );
		$this->assertSame( 'http://example.test/archive/', $callback( 'https://provider.guess/whatever/' ) );
	}

	/**
	 * Provider bridge: a configured canonical override wins over the endpoint
	 * URL, fed into the provider exactly as it would be in this plugin's own
	 * fragment.
	 *
	 * @return void
	 */
	public function test_provider_bridge_canonical_override(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'canonical' => 'https://site.test/all-stories/' ) )
		);
		$this->head( $this->detecting( SeoProvider::RankMath ) )->register();

		$callback = HookSpy::first( 'filter:rank_math/frontend/canonical' );
		$this->assertSame( 'https://site.test/all-stories/', $callback( 'https://provider.guess/whatever/' ) );
	}

	/**
	 * Provider bridge filters are inert off the archive request — every one
	 * passes its input straight through.
	 *
	 * @return void
	 */
	public function test_provider_bridge_inert_off_archive(): void {
		$this->head( $this->detecting( SeoProvider::Yoast ) )->register();

		$this->assertSame( 'X', HookSpy::first( 'filter:wpseo_title' )( 'X' ) );
		$this->assertSame( 'X', HookSpy::first( 'filter:wpseo_metadesc' )( 'X' ) );
		$this->assertSame( 'X', HookSpy::first( 'filter:wpseo_robots' )( 'X' ) );
		$this->assertSame( 'X', HookSpy::first( 'filter:wpseo_canonical' )( 'X' ) );
		$this->assertSame( array( 'X' ), HookSpy::first( 'filter:rank_math/frontend/robots' )( array( 'X' ) ) );
	}
}
