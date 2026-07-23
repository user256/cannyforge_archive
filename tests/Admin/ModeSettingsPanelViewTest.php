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
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
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
		$this->assertStringContainsString( 'Show posts published within the configured recent window, falling back to the latest posts when that window is empty.', $html );
		$this->assertStringNotContainsString( '<insert newscycle settings>', $html );
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
	 * Search Console results are presented as WordPress titles and links, not
	 * as the post IDs used by the runtime cache.
	 *
	 * @return void
	 */
	public function test_search_console_pages_are_meaningful_for_wordpress_users(): void {
		$cache = new SearchConsoleCacheStore(
			static function ( string $key, $fallback ) {
				return array( 'post_ids' => array( 17, 23 ) );
			},
			static function ( string $key, $value ): void {
			}
		);

		ob_start();
		( new ModeSettingsPanelView(
			null,
			$cache,
			static function ( int $post_id ): string {
				return 17 === $post_id ? 'How to prune a tree' : 'Making sourdough';
			},
			static function ( int $post_id ): string {
				return 'https://example.test/post-' . $post_id . '/';
			}
		) )->render(
			new Settings(),
			new GoogleSettings(),
			GoogleTokenStore::STATUS_DISCONNECTED,
			'',
			GoogleConnectionController::NOTICE_ERROR
		);
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Search Console top pages', $html );
		$this->assertStringContainsString( 'How to prune a tree', $html );
		$this->assertStringContainsString( 'Making sourdough', $html );
		$this->assertStringContainsString( 'data-url="https://example.test/post-17/"', $html );
		$this->assertStringContainsString( 'Add selected to curated URLs', $html );
		$this->assertStringNotContainsString( '>17<', $html );
	}

	/**
	 * Raw remote URLs remain visible when local URL resolution finds no posts.
	 *
	 * @return void
	 */
	public function test_unmatched_search_console_urls_are_shown_for_diagnostics(): void {
		$cache = new SearchConsoleCacheStore(
			static function ( string $key, $fallback ) {
				return array(
					'post_ids'    => array(),
					'source_urls' => array( 'https://production.example/post/' ),
				);
			},
			static function ( string $key, $value ): void {
			}
		);

		$html = $this->render(
			new Settings(),
			new GoogleSettings(),
			GoogleTokenStore::STATUS_DISCONNECTED,
			'',
			GoogleConnectionController::NOTICE_ERROR,
			$cache
		);

		$this->assertStringContainsString( 'Search Console returned 1 URLs', $html );
		$this->assertStringContainsString( 'https://production.example/post/', $html );
	}

	/**
	 * Raw GA4 page paths remain visible when local URL resolution finds no posts.
	 *
	 * @return void
	 */
	public function test_unmatched_ga4_paths_are_shown_for_diagnostics(): void {
		$cache = new Ga4CacheStore(
			static function ( string $key, $fallback ) {
				return array(
					'post_ids'    => array(),
					'source_urls' => array( '/production/post/' ),
				);
			},
			static function ( string $key, $value ): void {
			}
		);

		$html = $this->render(
			new Settings(),
			new GoogleSettings(),
			GoogleTokenStore::STATUS_DISCONNECTED,
			'',
			GoogleConnectionController::NOTICE_ERROR,
			null,
			$cache
		);

		$this->assertStringContainsString( 'GA4 returned 1 page paths', $html );
		$this->assertStringContainsString( '/production/post/', $html );
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
			'<script>alert(1)</script>',
			GoogleConnectionController::NOTICE_ERROR
		);

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
	}

	/**
	 * Render the panel and capture its output.
	 *
	 * @param Settings                     $settings        Current settings.
	 * @param GoogleSettings|null          $google_settings Current Google settings.
	 * @param string                       $google_status   Current Google connection status.
	 * @param string                       $notice          One-shot Google notice text.
	 * @param string                       $notice_type     Notice type.
	 * @param SearchConsoleCacheStore|null $search_console_cache Search Console cache.
	 * @param Ga4CacheStore|null           $ga4_cache       GA4 cache.
	 * @return string
	 */
	private function render(
		Settings $settings,
		?GoogleSettings $google_settings = null,
		string $google_status = GoogleTokenStore::STATUS_DISCONNECTED,
		string $notice = '',
		string $notice_type = GoogleConnectionController::NOTICE_ERROR,
		?SearchConsoleCacheStore $search_console_cache = null,
		?Ga4CacheStore $ga4_cache = null
	): string {
		ob_start();
		( new ModeSettingsPanelView( null, $search_console_cache, null, null, $ga4_cache ) )->render(
			$settings,
			$google_settings ?? new GoogleSettings(),
			$google_status,
			$notice,
			$notice_type
		);

		return (string) ob_get_clean();
	}
}
