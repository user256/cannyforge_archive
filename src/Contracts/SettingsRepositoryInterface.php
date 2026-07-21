<?php
/**
 * Settings repository contract.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Persistence boundary for the plugin settings.
 *
 * Implementations own where settings live (the WordPress options API, an
 * in-memory store for tests, …). The engine depends only on this contract,
 * never on a concrete store.
 */
interface SettingsRepositoryInterface {
	/**
	 * Load the current settings, falling back to defaults when none are stored.
	 *
	 * @return Settings The persisted settings, or defaults when unset.
	 */
	public function get(): Settings;

	/**
	 * Persist the given settings.
	 *
	 * @param Settings $settings The settings to store.
	 * @return void
	 */
	public function save( Settings $settings ): void;
}
