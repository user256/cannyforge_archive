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
use CannyForge\Archive\Integration\Google\GoogleSettings;
use PHPUnit\Framework\TestCase;

/**
 * The view renders the mock-up controls and the mode-dependent panel.
 */
class SettingsViewTest extends TestCase {
	private function render( Settings $settings ): string {
		ob_start();
		( new SettingsView() )->render( $settings, 'admin.php?page=cannyforge-archive' );
		return (string) ob_get_clean();
	}

	/**
	 * Both panels are rendered to support CSS-driven toggling.
	 *
	 * @return void
	 */
	public function test_renders_blog_panel(): void {
		$html = $this->render( Settings::from_array( array( 'mode' => 'blog' ) ) );

		$this->assertStringContainsString( 'Top Articles', $html );
		$this->assertStringContainsString( 'name="blog_urls"', $html );
		$this->assertStringContainsString( 'Google Top Content', $html );
		$this->assertStringContainsString( 'Open Google setup wizard', $html );
		$this->assertStringContainsString( 'name="google_client_id"', $html );
		$this->assertStringContainsString( 'name="google_client_secret"', $html );
		$this->assertStringContainsString( 'name="google_client_json"', $html );
		$this->assertStringContainsString( 'name="google_search_console_site_url"', $html );
		$this->assertStringContainsString( 'name="google_report_window_days"', $html );
		$this->assertStringContainsString( 'name="google_ga4_property_id"', $html );
		$this->assertStringContainsString( 'Connect Google', $html );
		$this->assertStringContainsString( 'Disconnect', $html );
		$this->assertStringContainsString( 'Refresh Search Console', $html );
		$this->assertStringContainsString( 'Refresh GA4', $html );
		$this->assertStringContainsString( 'data-cf-google-consent-copy', $html );
		$this->assertStringContainsString( 'Search Console (read-only)', $html );
		$this->assertStringNotContainsString( 'Google Analytics 4 (read-only)', $html );
	}

	/**
	 * The pre-connect consent copy names Analytics too once GA4 is configured
	 * (ticket 614: scopes shown must match what will actually be requested).
	 *
	 * @return void
	 */
	public function test_consent_copy_names_analytics_when_ga4_is_configured(): void {
		ob_start();
		( new SettingsView() )->render(
			Settings::from_array( array( 'mode' => 'blog' ) ),
			'admin.php?page=cannyforge-archive',
			'',
			new GoogleSettings( '', '', '', 30, '123456789' )
		);
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Search Console (read-only)', $html );
		$this->assertStringContainsString( 'Google Analytics 4 (read-only)', $html );
	}

	/**
	 * Both panels are rendered to support CSS-driven toggling.
	 *
	 * @return void
	 */
	public function test_renders_news_panel(): void {
		$html = $this->render( Settings::from_array( array( 'mode' => 'news' ) ) );

		$this->assertStringContainsString( 'News Cycle Settings', $html );
		$this->assertStringContainsString( 'name="news_window_hours"', $html );
		$this->assertStringContainsString( 'name="news_fallback_count"', $html );
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
				'name="pagination_style"',
				'name="link_title"',
				'name="link_description"',
				'name="link_featured_image"',
				'name="link_categories"',
				'name="link_tags"',
				'name="link_author"',
				'name="link_published_date"',
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
				'name="google_client_id"',
				'name="google_client_secret"',
				'name="google_client_json"',
				'name="google_search_console_site_url"',
				'name="google_report_window_days"',
				'name="google_ga4_property_id"',
				'name="seo_title"',
				'name="seo_meta_description"',
				'name="seo_index"',
				'name="seo_follow"',
				'name="seo_canonical"',
				'name="theme_layout"',

				'name="select_include_categories[]"',
				'name="select_exclude_tags[]"',
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
		$this->assertMatchesRegularExpression( '/name="link_categories"[^>]*checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="link_tags"[^>]*checked/', $html );
		$this->assertMatchesRegularExpression( '/name="link_author"[^>]*checked/', $html );
		$this->assertMatchesRegularExpression( '/name="link_published_date"[^>]*checked/', $html );
		$this->assertMatchesRegularExpression( '/name="theme_layout"[\s\S]*value="cards" selected/', $html );
		$this->assertMatchesRegularExpression( '/name="pagination_style"[\s\S]*value="leading" selected/', $html );
	}



	/**
	 * The page is renamed to "CannyForge Archive Generator" (not "HTML Sitemap Generator").
	 *
	 * @return void
	 */
	public function test_titled_archive_generator(): void {
		$html = $this->render( new Settings() );

		$this->assertStringContainsString( 'CannyForge Archive Generator', $html );
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
	 * The current header contract is a "Live Preview" toggle button plus an
	 * "Open" link to the archive in a new tab, and the side preview panel's
	 * iframe is pointed at the same URL (not the older single "Preview
	 * Archive" link).
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

		$this->assertStringContainsString( 'id="cf-preview-toggle"', $html );
		$this->assertStringContainsString( 'Live Preview', $html );
		$this->assertStringContainsString( 'href="http://example.test/archive/" target="_blank" rel="noopener noreferrer">Open', $html );
		$this->assertStringContainsString( '<iframe src="http://example.test/archive/" title="Preview"></iframe>', $html );
	}

	/**
	 * No preview toggle or "Open" link is rendered without a preview URL.
	 *
	 * @return void
	 */
	public function test_omits_preview_link_without_preview_url(): void {
		$html = $this->render( new Settings() );

		$this->assertStringNotContainsString( 'id="cf-preview-toggle"', $html );
		$this->assertStringNotContainsString( 'Live Preview', $html );
	}
}
