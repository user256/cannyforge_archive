<?php
/**
 * Tests for the settings view renderer.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\SettingsView;
use CannyForge\Archive\Contracts\Settings\Settings;
use PHPUnit\Framework\TestCase;

/**
 * The view renders the mock-up controls and the mode-dependent panel.
 */
class SettingsViewTest extends TestCase {
	/**
	 * Render to a string.
	 *
	 * @param Settings $settings Settings to render.
	 * @return string
	 */
	private function render( Settings $settings ): string {
		ob_start();
		( new SettingsView() )->render( $settings, 'admin.php?page=cannyforge-archive' );
		return (string) ob_get_clean();
	}

	/**
	 * Blog mode shows the Blog URL panel and not the News window.
	 *
	 * @return void
	 */
	public function test_blog_mode_renders_blog_panel(): void {
		$html = $this->render( Settings::from_array( array( 'mode' => 'blog' ) ) );

		$this->assertStringContainsString( 'Blog URLs to include', $html );
		$this->assertStringContainsString( 'name="blog_urls"', $html );
		$this->assertStringNotContainsString( 'News Sitemap Settings', $html );
	}

	/**
	 * News mode shows the News window panel and not the Blog URL list.
	 *
	 * @return void
	 */
	public function test_news_mode_renders_news_panel(): void {
		$html = $this->render( Settings::from_array( array( 'mode' => 'news' ) ) );

		$this->assertStringContainsString( 'News Sitemap Settings', $html );
		$this->assertStringContainsString( 'name="news_window_hours"', $html );
		$this->assertStringNotContainsString( 'name="blog_urls"', $html );
	}

	/**
	 * Every control from the mock-up is present, plus the nonce field.
	 *
	 * @return void
	 */
	public function test_renders_all_controls_and_nonce(): void {
		$html = $this->render( new Settings() );

		foreach (
			array(
				'name="mode"',
				'name="pagination_limit"',
				'name="link_title"',
				'name="link_description"',
				'name="link_featured_image"',
				'name="filter_search"',
				'name="filter_category"',
				'name="filter_tag"',
				'name="filter_month_year"',
				'name="filter_author"',
				SettingsView::NONCE_FIELD,
			) as $needle
		) {
			$this->assertStringContainsString( $needle, $html );
		}
	}

	/**
	 * Default link-type/filter state is reflected in the checkboxes.
	 *
	 * @return void
	 */
	public function test_defaults_are_checked(): void {
		$html = $this->render( new Settings() );

		// Title default-on; author default-off.
		$this->assertMatchesRegularExpression( '/name="link_title"[^>]*checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="filter_author"[^>]*checked/', $html );
	}
}
