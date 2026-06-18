<?php
/**
 * Plugin composition root.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Bootstrap;

/**
 * Composition root for CannyForge Archive.
 *
 * The only layer permitted to wire the engine (Core) and its contracts
 * together against WordPress hooks. Keep this thin: construct collaborators
 * and register hooks — no business logic lives here.
 */
class Plugin {
	/**
	 * Boot the plugin: register hooks and wire collaborators.
	 *
	 * @return void
	 */
	public function init(): void {
		// Wiring is registered here as the engine layers land (see tickets 1xx).
	}
}
