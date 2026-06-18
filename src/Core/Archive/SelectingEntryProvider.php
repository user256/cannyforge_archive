<?php
/**
 * Applies content selection to another provider's entries.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Decorates an entry provider, applying the content-selection rules (ticket 111)
 * to whatever it produces.
 *
 * Because it operates on the inner provider's entry list, the rules apply to
 * both News and Blog modes when this wraps the mode-dispatching provider. The
 * filtering/ordering itself lives in the pure {@see ContentSelector}.
 */
final class SelectingEntryProvider implements ArchiveEntryProviderInterface {
	/**
	 * The wrapped entry source.
	 *
	 * @var ArchiveEntryProviderInterface
	 */
	private ArchiveEntryProviderInterface $inner;

	/**
	 * The pure selection logic.
	 *
	 * @var ContentSelector
	 */
	private ContentSelector $selector;

	/**
	 * Construct the decorator.
	 *
	 * @param ArchiveEntryProviderInterface $inner    The wrapped provider.
	 * @param ContentSelector               $selector The selection logic.
	 */
	public function __construct( ArchiveEntryProviderInterface $inner, ContentSelector $selector ) {
		$this->inner    = $inner;
		$this->selector = $selector;
	}

	/**
	 * Provide the inner entries, filtered and ordered by content selection.
	 *
	 * @param Settings $settings Current settings.
	 * @return \CannyForge\Archive\Contracts\Archive\ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array {
		return $this->selector->select(
			$this->inner->provide( $settings ),
			$settings->content_selection()
		);
	}
}
