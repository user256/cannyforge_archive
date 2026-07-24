<?php
/**
 * A fixed, in-memory archive entry provider for tests.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Returns a caller-supplied list of entries verbatim.
 *
 * Test double for rendering and page-wiring tests; not part of the shipping
 * plugin (ticket 727).
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
