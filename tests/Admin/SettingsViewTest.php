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
				'name="seo_title"',
				'name="seo_meta_description"',
				'name="seo_index"',
				'name="seo_follow"',
				'name="seo_canonical"',
				'name="select_include_categories"',
				'name="select_exclude_tags"',
				'name="select_exclude_noindex"',
				'name="select_pinned_urls"',
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
	 * When a base URL is supplied, the CannyForge wordmark is rendered.
	 *
	 * @return void
	 */
	public function test_renders_brand_logo_when_base_url_set(): void {
		$html = $this->render( new Settings(), 'http://example.test/wp-content/plugins/cannyforge-archive/' );

		$this->assertStringContainsString( 'cf-brand-header', $html );
		$this->assertStringContainsString( 'assets/branding/cannyforge-font-dark.svg', $html );
	}

	/**
	 * With no base URL, the header falls back to the text wordmark, no image.
	 *
	 * @return void
	 */
	public function test_omits_logo_without_base_url(): void {
		$html = $this->render( new Settings() );

		$this->assertStringContainsString( 'Archive Generator', $html );
		$this->assertStringContainsString( '>cannyforge<', $html );
		$this->assertStringNotContainsString( 'cannyforge-font-dark.svg', $html );
	}

	/**
	 * The page is renamed to "Archive Generator" (not "HTML Sitemap Generator").
	 *
	 * @return void
	 */
	public function test_titled_archive_generator(): void {
		$html = $this->render( new Settings() );

		$this->assertStringContainsString( 'Archive Generator', $html );
		$this->assertStringNotContainsString( 'HTML Sitemap Generator Settings', $html );
	}

	/**
	 * The CSV import controls render in Blog mode.
	 *
	 * @return void
	 */
	public function test_renders_csv_import_controls(): void {
		$html = $this->render( Settings::from_array( array( 'mode' => 'blog' ) ) );

		$this->assertStringContainsString( 'name="blog_urls_csv"', $html );
		$this->assertStringContainsString( 'name="blog_urls_csv_replace"', $html );
		$this->assertStringContainsString( 'multipart/form-data', $html );
	}

	/**
	 * A preview link is rendered when a preview URL is supplied.
	 *
	 * @return void
	 */
	public function test_renders_preview_link(): void {
		ob_start();
		( new SettingsView() )->render(
			new Settings(),
			'admin.php?page=cannyforge-archive',
			'http://example.test/archive/'
		);
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Preview archive', $html );
		$this->assertStringContainsString( 'href="http://example.test/archive/"', $html );
		$this->assertStringContainsString( 'target="_blank"', $html );
	}
}
