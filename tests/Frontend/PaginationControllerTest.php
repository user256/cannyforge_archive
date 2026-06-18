<?php
/**
 * Tests for the front-end pagination replacement controller.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Core\Pagination\PaginationRenderer;
use CannyForge\Archive\Core\Pagination\TargetingPredicate;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\PaginationController;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * The controller wires its hooks and only replaces pagination where targeted.
 */
class PaginationControllerTest extends TestCase {
	/**
	 * Reset shared state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		OptionStore::reset();
	}

	/**
	 * Build a controller with the real (option-backed) repository.
	 *
	 * @return PaginationController
	 */
	private function controller(): PaginationController {
		return new PaginationController(
			new OptionsSettingsRepository(),
			new TargetingPredicate(),
			new PaginationRenderer()
		);
	}

	/**
	 * Registration wires the pagination filter and the shortcode.
	 *
	 * @return void
	 */
	public function test_register_wires_filter_and_shortcode(): void {
		$this->controller()->register();

		$this->assertTrue( HookSpy::has( 'filter:navigation_markup_template' ) );
		$this->assertTrue( HookSpy::has( 'shortcode:' . PaginationController::SHORTCODE ) );
	}

	/**
	 * Off a targeted archive (the test runtime is no archive type), the original
	 * pagination template is returned untouched — no double-render, no hijack.
	 *
	 * @return void
	 */
	public function test_leaves_untargeted_requests_untouched(): void {
		$original = '<nav class="navigation pagination">%s</nav>';

		$result = $this->controller()->filter_pagination( $original, 'pagination' );

		$this->assertSame( $original, $result );
	}

	/**
	 * Non-pagination navigation blocks (e.g. post-to-post nav) are never touched,
	 * even though they share the filter.
	 *
	 * @return void
	 */
	public function test_ignores_non_pagination_navigation(): void {
		$original = '<nav class="navigation post-navigation">%s</nav>';

		$result = $this->controller()->filter_pagination( $original, 'post-navigation' );

		$this->assertSame( $original, $result );
	}

	/**
	 * The shortcode renders the block unconditionally (explicit placement).
	 *
	 * @return void
	 */
	public function test_shortcode_renders_the_block(): void {
		$markup = $this->controller()->shortcode();

		$this->assertStringContainsString( 'cannyforge-pagination', $markup );
		$this->assertStringContainsString( 'View Archive', $markup );
	}

	/**
	 * A configured archive-URL override is used for the "View Archive" link.
	 *
	 * @return void
	 */
	public function test_shortcode_honours_configured_archive_url(): void {
		OptionStore::set(
			OptionsSettingsRepository::OPTION_KEY,
			array( 'archive_url' => 'https://elsewhere.test/all/' )
		);

		$markup = $this->controller()->shortcode();

		$this->assertStringContainsString( 'href="https://elsewhere.test/all/"', $markup );
	}

	/**
	 * With no override, the link falls back to the archive endpoint URL.
	 *
	 * @return void
	 */
	public function test_shortcode_falls_back_to_endpoint_url(): void {
		$markup = $this->controller()->shortcode();

		$this->assertStringContainsString( 'href="http://example.test/archive/"', $markup );
	}
}
