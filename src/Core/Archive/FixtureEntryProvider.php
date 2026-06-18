<?php
/**
 * A fixed, in-memory archive entry provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Returns a caller-supplied list of entries verbatim.
 *
 * The placeholder source for ticket 103 until the News (ticket 104) and Blog
 * (ticket 105) providers land, and a convenient seam for rendering tests.
 */
final class FixtureEntryProvider implements ArchiveEntryProviderInterface {
	/**
	 * The fixed entries.
	 *
	 * @var ArchiveEntry[]
	 */
	private array $entries;

	/**
	 * Construct with a fixed entry list.
	 *
	 * @param ArchiveEntry[] $entries The entries to return.
	 */
	public function __construct( array $entries = array() ) {
		$this->entries = array_values( $entries );
	}

	/**
	 * Provide the fixed entries, ignoring the settings.
	 *
	 * @param Settings $settings Current settings (unused).
	 * @return ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array {
		unset( $settings );

		return $this->entries;
	}
}
