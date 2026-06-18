<?php
/**
 * Archive entry provider contract.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Archive;

use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Supplies the entries the archive renders.
 *
 * Implementations decide where entries come from — the News recent-window query
 * (ticket 104), the Blog top-URL list (ticket 105), or a fixture in tests. The
 * renderer never knows which.
 */
interface ArchiveEntryProviderInterface {
	/**
	 * Provide the archive entries for the given settings.
	 *
	 * @param Settings $settings The current plugin settings.
	 * @return ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array;
}
