<?php
/**
 * Tests for the content-selection provider decorator.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\ContentSelection;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\ContentSelector;
use CannyForge\Archive\Core\Archive\SelectingEntryProvider;
use CannyForge\Archive\Tests\FixtureEntryProvider;
use PHPUnit\Framework\TestCase;

/**
 * The decorator applies the selection rules to whatever the inner provider gives.
 */
class SelectingEntryProviderTest extends TestCase {
	/**
	 * The decorator filters the inner provider's entries per the settings.
	 *
	 * @return void
	 */
	public function test_applies_selection_to_inner_entries(): void {
		$inner = new FixtureEntryProvider(
			array(
				new ArchiveEntry( 'a', 'a', '', '', array( 'News' ) ),
				new ArchiveEntry( 'b', 'b', '', '', array( 'Sport' ) ),
			)
		);

		$provider = new SelectingEntryProvider( $inner, new ContentSelector() );

		$settings = new Settings(
			content_selection: new ContentSelection( array( 'News' ) )
		);

		$result = $provider->provide( $settings );

		$this->assertCount( 1, $result );
		$this->assertSame( 'a', $result[0]->url() );
	}

	/**
	 * With default (empty) rules, the inner entries pass through untouched.
	 *
	 * @return void
	 */
	public function test_passes_through_with_default_rules(): void {
		$inner = new FixtureEntryProvider(
			array( new ArchiveEntry( 'a' ), new ArchiveEntry( 'b' ) )
		);

		$provider = new SelectingEntryProvider( $inner, new ContentSelector() );

		$result = $provider->provide( new Settings() );

		$this->assertCount( 2, $result );
	}
}
