<?php
/**
 * Mode-dispatching archive entry provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Delegates to the News or Blog provider based on the configured mode.
 *
 * Keeps the mode→provider choice as testable engine logic rather than wiring in
 * the composition root, and itself satisfies the provider contract so the
 * front-end page is unaware of the split.
 */
final class ModeEntryProvider implements ArchiveEntryProviderInterface {
	/**
	 * Provider used in News mode.
	 *
	 * @var ArchiveEntryProviderInterface
	 */
	private ArchiveEntryProviderInterface $news;

	/**
	 * Provider used in Blog mode.
	 *
	 * @var ArchiveEntryProviderInterface
	 */
	private ArchiveEntryProviderInterface $blog;

	/**
	 * Construct with the two mode providers.
	 *
	 * @param ArchiveEntryProviderInterface $news News-mode provider.
	 * @param ArchiveEntryProviderInterface $blog Blog-mode provider.
	 */
	public function __construct( ArchiveEntryProviderInterface $news, ArchiveEntryProviderInterface $blog ) {
		$this->news = $news;
		$this->blog = $blog;
	}

	/**
	 * Provide entries from the provider matching the configured mode.
	 *
	 * @param Settings $settings Current settings.
	 * @return \CannyForge\Archive\Contracts\Archive\ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array {
		$provider = Mode::News === $settings->mode() ? $this->news : $this->blog;

		return $provider->provide( $settings );
	}
}
