<?php
/**
 * WordPress options-backed settings repository.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Settings;

use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Stores the settings in a single WordPress option.
 *
 * The whole {@see Settings} snapshot round-trips through one option key as an
 * associative array, so adding a field never needs a migration.
 */
final class OptionsSettingsRepository implements SettingsRepositoryInterface {
	/**
	 * The option key the settings live under.
	 */
	public const OPTION_KEY = 'cannyforge_archive_settings';

	/**
	 * Load the current settings, falling back to defaults when none are stored.
	 *
	 * @return Settings
	 */
	public function get(): Settings {
		$stored = get_option( self::OPTION_KEY, array() );

		return Settings::from_array( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Persist the given settings as a plain array.
	 *
	 * @param Settings $settings The settings to store.
	 * @return void
	 */
	public function save( Settings $settings ): void {
		update_option( self::OPTION_KEY, $settings->to_array() );
	}
}
