<?php
/**
 * Plugin composition root.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Admin\AdminAssets;
use CannyForge\Archive\Admin\Ga4RefreshController;
use CannyForge\Archive\Admin\GoogleConnectionController;
use CannyForge\Archive\Admin\SearchConsoleRefreshController;
use CannyForge\Archive\Admin\SettingsFormParser;
use CannyForge\Archive\Admin\SettingsPage;
use CannyForge\Archive\Admin\SettingsView;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Core\Cache\CacheInvalidator;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\BlogEntryProvider;
use CannyForge\Archive\Core\Archive\ContentIndexProvider;
use CannyForge\Archive\Core\Archive\CompositePopularPostsSource;
use CannyForge\Archive\Core\Archive\ContentSelector;
use CannyForge\Archive\Core\Archive\FilterOptionsProvider;
use CannyForge\Archive\Core\Archive\JetpackStatsSource;
use CannyForge\Archive\Core\Archive\ModeEntryProvider;
use CannyForge\Archive\Core\Archive\NewsEntryProvider;
use CannyForge\Archive\Core\Archive\SelectingEntryProvider;
use CannyForge\Archive\Core\Archive\ThemeCssBuilder;
use CannyForge\Archive\Core\Cache\SearchResultCache;
use CannyForge\Archive\Core\Pagination\PaginationRenderer;
use CannyForge\Archive\Core\Pagination\TargetingPredicate;
use CannyForge\Archive\Core\RateLimit\SearchThrottle;
use CannyForge\Archive\Core\Seo\HeadTagBuilder;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchiveAssets;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Frontend\ArchiveSearchEndpoint;
use CannyForge\Archive\Frontend\PaginationController;
use CannyForge\Archive\Frontend\SeoHead;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use CannyForge\Archive\Integration\Google\Ga4CachedPopularPostsSource;
use CannyForge\Archive\Integration\Google\Ga4Client;
use CannyForge\Archive\Integration\Google\Ga4TopContentRefresher;
use CannyForge\Archive\Integration\Google\GoogleOauthClient;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCacheStore;
use CannyForge\Archive\Integration\Google\SearchConsoleCachedPopularPostsSource;
use CannyForge\Archive\Integration\Google\SearchConsoleClient;
use CannyForge\Archive\Integration\Google\SearchConsoleTopContentRefresher;

/**
 * Composition root for CannyForge Archive.
 *
 * The only layer permitted to wire the engine (Core), the admin/front-end
 * surfaces, and the contracts together against WordPress hooks. Keep this thin:
 * construct collaborators and register hooks — no business logic lives here.
 */
class Plugin {
	/**
	 * Boot the plugin: register hooks and wire collaborators.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->register_admin();
		$this->register_frontend();
	}

	/**
	 * Wire and register the admin settings page.
	 *
	 * @return void
	 */
	private function register_admin(): void {
		$repository      = new OptionsSettingsRepository();
		$google_settings = new GoogleSettingsStore();
		$google_tokens   = new GoogleTokenStore();
		$search_cache    = new SearchConsoleCacheStore();

		$page = new SettingsPage(
			$repository,
			new SettingsFormParser(),
			new SettingsView(),
			null,
			$google_settings,
			$google_tokens,
			$search_cache
		);
		$page->register();

		$google = new GoogleConnectionController( $google_settings, $google_tokens, $search_cache );
		$google->register();

		$refresh = new SearchConsoleRefreshController(
			$repository,
			$google_settings,
			$google_tokens,
			new SearchConsoleTopContentRefresher(
				new SearchConsoleClient( $this->google_oauth( $google_settings, $google_tokens ) ),
				$search_cache,
				$google_settings
			)
		);
		$refresh->register();

		$ga4_refresh = new Ga4RefreshController(
			$repository,
			$google_settings,
			$google_tokens,
			new Ga4TopContentRefresher(
				new Ga4Client( $this->google_oauth( $google_settings, $google_tokens ) ),
				new Ga4CacheStore(),
				$google_settings
			)
		);
		$ga4_refresh->register();

		$assets = new AdminAssets( $this->base_url(), $this->version() );
		$assets->register();
	}

	/**
	 * Build a Google OAuth client from the stored configuration.
	 *
	 * Shared by the Search Console and GA4 report clients so both consume the
	 * same refresh-token connection.
	 *
	 * @param GoogleSettingsStore $settings Google settings store.
	 * @param GoogleTokenStore    $tokens   Google token store.
	 * @return GoogleOauthClient
	 */
	private function google_oauth( GoogleSettingsStore $settings, GoogleTokenStore $tokens ): GoogleOauthClient {
		return new GoogleOauthClient(
			$tokens,
			$settings->get()->client_id(),
			$settings->get()->client_secret()
		);
	}

	/**
	 * Wire and register the front-end archive page.
	 *
	 * The entry source is mode-aware: News mode uses the recent-window query;
	 * Blog mode uses the curated URL list.
	 *
	 * @return void
	 */
	private function register_frontend(): void {
		$repository = new OptionsSettingsRepository();
		$predicate  = new TargetingPredicate();

		$provider = $this->build_entry_provider();

		$cache        = new ArchiveCache();
		$search_cache = new SearchResultCache();
		$renderer     = new ArchiveRenderer();

		$page = new ArchivePage(
			$repository,
			$provider,
			$renderer,
			ArchivePage::DEFAULT_SLUG,
			$cache,
			new FilterOptionsProvider()
		);
		$page->register();

		$search = new ArchiveSearchEndpoint(
			$repository,
			new ContentIndexProvider(),
			$renderer,
			$search_cache,
			new SearchThrottle()
		);
		$search->register();

		$invalidator = new CacheInvalidator( $cache, $search_cache );
		$invalidator->register();

		$pagination = new PaginationController(
			$repository,
			$predicate,
			new PaginationRenderer()
		);
		$pagination->register();

		$assets = new ArchiveAssets(
			$repository,
			$predicate,
			$this->base_url(),
			$this->version(),
			new ThemeCssBuilder()
		);
		$assets->register();

		$seo = new SeoHead(
			$repository,
			new HeadTagBuilder()
		);
		$seo->register();
	}

	/**
	 * Build the mode-aware archive entry provider.
	 *
	 * News mode uses the recent-window query; Blog mode uses the curated URL
	 * list with a Search Console / Jetpack popularity fallback.
	 *
	 * @return SelectingEntryProvider
	 */
	private function build_entry_provider(): SelectingEntryProvider {
		$google_settings = new GoogleSettingsStore();
		$google_tokens   = new GoogleTokenStore();

		// Google signal precedence: Search Console first, GA4 second. GA4 is
		// additive — it only contributes when Search Console yields nothing.
		$google = new CompositePopularPostsSource(
			new SearchConsoleCachedPopularPostsSource( new SearchConsoleCacheStore(), $google_settings, $google_tokens ),
			new Ga4CachedPopularPostsSource( new Ga4CacheStore(), $google_settings, $google_tokens )
		);

		return new SelectingEntryProvider(
			new ModeEntryProvider(
				new NewsEntryProvider(),
				new BlogEntryProvider(
					$google,
					new JetpackStatsSource()
				)
			),
			new ContentSelector()
		);
	}

	/**
	 * The plugin's base URL, resolved from the main file when WordPress is loaded.
	 *
	 * @return string
	 */
	private function base_url(): string {
		if ( defined( 'CANNYFORGE_ARCHIVE_FILE' ) && function_exists( 'plugin_dir_url' ) ) {
			return plugin_dir_url( CANNYFORGE_ARCHIVE_FILE );
		}

		return '';
	}

	/**
	 * The plugin version, for asset cache-busting.
	 *
	 * @return string
	 */
	private function version(): string {
		return defined( 'CANNYFORGE_ARCHIVE_VERSION' ) ? CANNYFORGE_ARCHIVE_VERSION : '0';
	}
}
