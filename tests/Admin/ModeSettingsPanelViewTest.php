<?php
/**
 * Snapshot-style render tests for the mode-dependent settings panel.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Admin\ModeSettingsPanelView;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use PHPUnit\Framework\TestCase;

/**
 * The current News/Blog settings values appear in the rendered output, and
 * every user-controlled value is escaped rather than echoed raw.
 */
class ModeSettingsPanelViewTest extends TestCase {
	/**
	 * The News panel reflects the current window/fallback values.
	 *
	 * @return void
	 */
	public function test_news_panel_reflects_current_values(): void {
		$html = $this->render(
			Settings::from_array(
				array(
					'mode'                => 'news',
					'news_window_hours'   => 48,
					'news_fallback_count' => 25,
				)
			)
		);

		$this->assertStringContainsString( 'name="news_window_hours" value="48"', $html );
		$this->assertStringContainsString( 'name="news_fallback_count" value="25"', $html );
	}

	/**
	 * The Blog panel reflects the current URL cap and curated URL list.
	 *
	 * @return void
	 */
	public function test_blog_panel_reflects_current_values(): void {
		$html = $this->render(
			Settings::from_array(
				array(
					'mode'          => 'blog',
					'blog_max_urls' => 42,
					'blog_urls'     => array( 'https://example.test/a/', 'https://example.test/b/' ),
				)
			)
		);

		$this->assertStringContainsString( 'name="blog_max_urls" value="42"', $html );
		$this->assertStringContainsString( 'https://example.test/a/', $html );
		$this->assertStringContainsString( 'https://example.test/b/', $html );
	}

	/**
	 * The condensed Google summary reflects the current Google settings and
	 * connection status.
	 *
	 * @return void
	 */
	public function test_google_summary_reflects_current_configuration(): void {
		$html = $this->render(
			new Settings(),
			new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com', 30, '123456789' ),
			GoogleTokenStore::STATUS_CONNECTED
		);

		$this->assertStringContainsString( 'is-good">Saved', $html );
		$this->assertStringContainsString( 'is-good">Connected', $html );
		$this->assertStringContainsString( 'is-good">Ready', $html );
		$this->assertStringContainsString( 'is-good">Enabled', $html );
	}

	/**
	 * A curated URL containing a script tag is rendered entity-encoded, not
	 * as literal, executable markup — the textarea is user-controlled free
	 * text, so it must never be echoed raw.
	 *
	 * @return void
	 */
	public function test_blog_urls_are_escaped_against_xss(): void {
		$html = $this->render(
			Settings::from_array(
				array(
					'mode'      => 'blog',
					'blog_urls' => array( '<script>alert(1)</script>' ),
				)
			)
		);

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
	}

	/**
	 * A one-shot Google notice containing a script tag is rendered
	 * entity-encoded: the notice text is relayed from a query-string value
	 * and must never be echoed raw.
	 *
	 * @return void
	 */
	public function test_google_notice_is_escaped_against_xss(): void {
		$html = $this->render(
			new Settings(),
			new GoogleSettings(),
			GoogleTokenStore::STATUS_DISCONNECTED,
			false,
			'',
			'',
			'<script>alert(1)</script>',
			GoogleConnectionController::NOTICE_ERROR
		);

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
	}

	/**
	 * Render the panel and capture its output.
	 *
	 * @param Settings            $settings        Current settings.
	 * @param GoogleSettings|null $google_settings Current Google settings.
	 * @param string              $google_status   Current Google connection status.
	 * @param bool                $secret_saved    Whether a client secret is already stored.
	 * @param string              $connect_url     Connect action URL.
	 * @param string              $disconnect_url  Disconnect action URL.
	 * @param string              $notice          One-shot Google notice text.
	 * @param string              $notice_type     Notice type.
	 * @return string
	 */
	private function render(
		Settings $settings,
		?GoogleSettings $google_settings = null,
		string $google_status = GoogleTokenStore::STATUS_DISCONNECTED,
		bool $secret_saved = false,
		string $connect_url = '',
		string $disconnect_url = '',
		string $notice = '',
		string $notice_type = GoogleConnectionController::NOTICE_ERROR
	): string {
		ob_start();
		( new ModeSettingsPanelView() )->render(
			$settings,
			$google_settings ?? new GoogleSettings(),
			$google_status,
			$secret_saved,
			$connect_url,
			$disconnect_url,
			$notice,
			$notice_type
		);

		return (string) ob_get_clean();
	}
}
