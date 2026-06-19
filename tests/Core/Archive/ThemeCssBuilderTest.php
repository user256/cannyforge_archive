<?php
/**
 * Tests for the theme CSS builder.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\Theme;
use CannyForge\Archive\Core\Archive\ThemeCssBuilder;
use CannyForge\Archive\Tests\HookSpy;
use PHPUnit\Framework\TestCase;

/**
 * The theme builder turns settings into the expected CSS variable block.
 */
class ThemeCssBuilderTest extends TestCase {
	/**
	 * Reset shared state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
	}

	/**
	 * The configured colours are mapped into CSS variables for both blocks.
	 *
	 * @return void
	 */
	public function test_builds_css_variables_for_archive_and_pagination(): void {
		$css = ( new ThemeCssBuilder() )->build(
			new Theme( Theme::LAYOUT_LIST, '#112233', '#f5f5f5', '#223344', '#ddeeff' )
		);

		$this->assertStringContainsString( '.cannyforge-archive,.cannyforge-pagination', $css );
		$this->assertStringContainsString( '--cf-accent:#112233', $css );
		$this->assertStringContainsString( '--cf-surface:#f5f5f5', $css );
		$this->assertStringContainsString( '--cf-text:#223344', $css );
		$this->assertStringContainsString( '--cf-border:#ddeeff', $css );
	}

	/**
	 * The cannyforge_archive_theme_css filter is applied so themes can override CSS.
	 *
	 * @return void
	 */
	public function test_theme_css_filter_is_applied(): void {
		add_filter(
			'cannyforge_archive_theme_css',
			static fn ( string $css ): string => $css . '/* extra */'
		);

		$css = ( new ThemeCssBuilder() )->build(
			new Theme( Theme::LAYOUT_LIST, '#000', '#fff', '#111', '#222' )
		);

		$this->assertStringContainsString( '/* extra */', $css );
	}
}
