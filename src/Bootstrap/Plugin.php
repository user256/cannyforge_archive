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
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;

/**
 * Composition root for CannyForge Archive.
 *
 * The only layer permitted to wire the engine (Core), the admin surface, and
 * the contracts together against WordPress hooks. Keep this thin: construct
 * collaborators and register hooks — no business logic lives here.
 */
class Plugin {
	/**
	 * Boot the plugin: register hooks and wire collaborators.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->register_admin();
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
}
