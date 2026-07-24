<?php
/**
 * Tests for the archive SEO head output.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Core\Seo\HeadTagBuilder;
use CannyForge\Archive\Core\Seo\SeoProviderDetector;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Frontend\SeoHead;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * SEO tags emit only on the archive request, never elsewhere, with no
 * third-party SEO plugin active. See {@see SeoHeadProviderInteropTest} for
 * the Yoast SEO / Rank Math interop coverage (ticket 615).
 */
class SeoHeadTest extends TestCase {
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
	 * Build a SEO head controller with the real repository and no SEO plugin
	 * active.
	 *
	 * @return SeoHead
	 */
	private function head(): SeoHead {
		return new SeoHead(
			new OptionsSettingsRepository(),
			new HeadTagBuilder(),
			ArchivePage::DEFAULT_SLUG,
			null,
			new SeoProviderDetector( static fn (): bool => false, static fn (): bool => false )
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
	 * Registration wires the wp_head hook.
	 *
	 * @return void
	 */
	public function test_register_wires_head_hook(): void {
		$this->head()->register();

		$this->assertTrue( HookSpy::has( 'wp_head' ) );
	}

	/**
	 * On the archive request, the robots and canonical tags are emitted.
	 *
	 * @return void
	 */
	public function test_emits_on_archive_request(): void {
		$this->onArchive();

		ob_start();
		$this->head()->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertStringContainsString( '<meta name="robots" content="index,follow">', $out );
		$this->assertStringContainsString( '<link rel="canonical"', $out );
	}

	/**
	 * Off the archive request, nothing is emitted (no duplicate tags).
	 *
	 * @return void
	 */
	public function test_silent_off_archive(): void {
		ob_start();
		$this->head()->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertSame( '', $out );
	}

	/**
	 * Configured SEO values are reflected in the head output (title is handled by
	 * the document-title filter, so it is not in the head fragment).
	 *
	 * @return void
	 */
	public function test_reflects_configured_values(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array(
				'seo' => array(
					'title'     => 'Story Archive',
					'index'     => false,
					'canonical' => 'https://site.test/all/',
				),
			)
		);

		ob_start();
		$this->head()->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertStringNotContainsString( '<title>', $out );
		$this->assertStringContainsString( 'content="noindex,follow"', $out );
		$this->assertStringContainsString( 'href="https://site.test/all/"', $out );
	}

	/**
	 * The pagination-link destination (`archive_url`) is never used as the
	 * canonical — only the archive's own endpoint URL or the explicit SEO
	 * canonical override are candidates (ticket 615/612 canonical contract).
	 *
	 * @return void
	 */
	public function test_pagination_destination_never_becomes_canonical(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'archive_url' => 'https://site.test/view-everything/' )
		);

		ob_start();
		$this->head()->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertStringContainsString( 'href="http://example.test/archive/"', $out );
		$this->assertStringNotContainsString( 'view-everything', $out );
	}

	/**
	 * On the archive, the configured title overrides the document title.
	 *
	 * @return void
	 */
	public function test_filter_title_overrides_on_archive(): void {
		$this->onArchive();
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'seo' => array( 'title' => 'Story Archive' ) )
		);

		$this->assertSame( 'Story Archive', $this->head()->filter_title( 'Theme Default' ) );
	}

	/**
	 * Off the archive, the document title is left unchanged.
	 *
	 * @return void
	 */
	public function test_filter_title_untouched_off_archive(): void {
		$this->assertSame( 'Theme Default', $this->head()->filter_title( 'Theme Default' ) );
	}

	/**
	 * With no configured title, the archive keeps the theme/site default.
	 *
	 * @return void
	 */
	public function test_filter_title_falls_back_when_unset(): void {
		$this->onArchive();

		$this->assertSame( 'Theme Default', $this->head()->filter_title( 'Theme Default' ) );
	}

	/**
	 * Exhausted full-archive URLs set is_404 before wp_head; they must not
	 * receive archive robots/canonical (ticket 729).
	 *
	 * @return void
	 */
	public function test_silent_on_exhausted_continuation_404(): void {
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'full_archive_pagination' => true )
		);
		$GLOBALS['wp_query'] = (object) array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'query_vars' => array( ArchivePage::QUERY_VAR => 'page/999' ),
			'is_404'     => true,
		);

		ob_start();
		$this->head()->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertSame( '', $out );
		$this->assertSame( 'Theme Default', $this->head()->filter_title( 'Theme Default' ) );
	}

	/**
	 * In-range continuation pages still emit default archive SEO.
	 *
	 * @return void
	 */
	public function test_emits_on_in_range_continuation_request(): void {
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'full_archive_pagination' => true )
		);
		$GLOBALS['wp_query'] = (object) array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'query_vars' => array( ArchivePage::QUERY_VAR => 'page/2' ),
			'is_404'     => false,
		);

		ob_start();
		$this->head()->maybe_render();
		$out = (string) ob_get_clean();

		$this->assertStringContainsString( '<meta name="robots" content="index,follow">', $out );
		$this->assertStringContainsString( 'href="http://example.test/archive/page/2/"', $out );
	}
}
