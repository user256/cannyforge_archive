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
	 * @param string   $base_url Plugin base URL (for the brand logo).
	 * @return string
	 */
	private function render( Settings $settings, string $base_url = '' ): string {
		ob_start();
		( new SettingsView( $base_url ) )->render( $settings, 'admin.php?page=cannyforge-archive' );
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
				'name="target_category"',
				'name="target_tag"',
				'name="target_author"',
				'name="target_date"',
				'name="archive_url"',
				SettingsView::NONCE_FIELD,
			) as $needle
		) {
			$this->assertStringContainsString( $needle, $html );
		}
	}

	/**
	 * Default link-type/filter/targeting state is reflected in the checkboxes.
	 *
	 * @return void
	 */
	public function test_defaults_are_checked(): void {
		$html = $this->render( new Settings() );

		// Title default-on; author default-off; targeting defaults (cat/tag on).
		$this->assertMatchesRegularExpression( '/name="link_title"[^>]*checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="filter_author"[^>]*checked/', $html );
		$this->assertMatchesRegularExpression( '/name="target_category"[^>]*checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="target_date"[^>]*checked/', $html );
	}

	/**
	 * When a base URL is supplied, the CannyForge brand logo is rendered.
	 *
	 * @return void
	 */
	public function test_renders_brand_logo_when_base_url_set(): void {
		$html = $this->render( new Settings(), 'http://example.test/wp-content/plugins/cannyforge-archive/' );

		$this->assertStringContainsString( 'cannyforge-archive-brand__logo', $html );
		$this->assertStringContainsString( 'assets/branding/cannyforge-font-dark.svg', $html );
	}

	/**
	 * With no base URL, the header still renders the title but no logo image.
	 *
	 * @return void
	 */
	public function test_omits_logo_without_base_url(): void {
		$html = $this->render( new Settings() );

		$this->assertStringContainsString( 'HTML Sitemap Generator Settings', $html );
		$this->assertStringNotContainsString( 'cannyforge-archive-brand__logo', $html );
	}
}
