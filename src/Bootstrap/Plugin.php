<?php
/**
 * Plugin composition root.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Bootstrap;

use CannyForge\Archive\Admin\SettingsFormParser;
use CannyForge\Archive\Admin\SettingsPage;
use CannyForge\Archive\Admin\SettingsView;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\FixtureEntryProvider;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchivePage;

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
	}

	/**
	 * Wire and register the front-end archive page.
	 *
	 * The entry source is the fixture provider for now; the News (ticket 104)
	 * and Blog (ticket 105) providers replace it once they land.
	 *
	 * @return void
	 */
	private function register_frontend(): void {
		$page = new ArchivePage(
			new OptionsSettingsRepository(),
			new FixtureEntryProvider(),
			new ArchiveRenderer()
		);
		$page->register();
	}
}
