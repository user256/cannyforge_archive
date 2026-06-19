<?php
/**
 * Plugin composition root.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Bootstrap;

use CannyForge\Archive\Admin\AdminAssets;
use CannyForge\Archive\Admin\SettingsFormParser;
use CannyForge\Archive\Admin\SettingsPage;
use CannyForge\Archive\Admin\SettingsView;
use CannyForge\Archive\Core\Cache\ArchiveCache;
use CannyForge\Archive\Core\Cache\CacheInvalidator;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\BlogEntryProvider;
use CannyForge\Archive\Core\Archive\ContentSelector;
use CannyForge\Archive\Core\Archive\ModeEntryProvider;
use CannyForge\Archive\Core\Archive\NewsEntryProvider;
use CannyForge\Archive\Core\Archive\SelectingEntryProvider;
use CannyForge\Archive\Core\Archive\ThemeCssBuilder;
use CannyForge\Archive\Core\Pagination\PaginationRenderer;
use CannyForge\Archive\Core\Pagination\TargetingPredicate;
use CannyForge\Archive\Core\Seo\HeadTagBuilder;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchiveAssets;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Frontend\PaginationController;
use CannyForge\Archive\Frontend\SeoHead;

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
		$page = new SettingsPage(
			new OptionsSettingsRepository(),
			new SettingsFormParser(),
			new SettingsView()
		);
		$page->register();

		$assets = new AdminAssets( $this->base_url(), $this->version() );
		$assets->register();
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

		$provider = new SelectingEntryProvider(
			new ModeEntryProvider(
				new NewsEntryProvider(),
				new BlogEntryProvider()
			),
			new ContentSelector()
		);

		$cache = new ArchiveCache();

		$page = new ArchivePage(
			$repository,
			$provider,
			new ArchiveRenderer(),
			ArchivePage::DEFAULT_SLUG,
			$cache
		);
		$page->register();

		$invalidator = new CacheInvalidator( $cache );
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
