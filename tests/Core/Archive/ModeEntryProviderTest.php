<?php
/**
 * Tests for the mode-dispatching entry provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\ModeEntryProvider;
use PHPUnit\Framework\TestCase;

/**
 * The dispatcher routes to the provider matching the configured mode.
 */
class ModeEntryProviderTest extends TestCase {
	/**
	 * Build a stub provider returning a single entry with the given URL.
	 *
	 * @param string $url Marker URL.
	 * @return ArchiveEntryProviderInterface
	 */
	private function stub( string $url ): ArchiveEntryProviderInterface {
		return new class( $url ) implements ArchiveEntryProviderInterface {
			/**
			 * Marker URL.
			 *
			 * @var string
			 */
			private string $url;

			/**
			 * Construct with a marker URL.
			 *
			 * @param string $url Marker URL.
			 */
			public function __construct( string $url ) {
				$this->url = $url;
			}

			/**
			 * Provide a single entry carrying the marker URL.
			 *
			 * @param Settings $settings Unused.
			 * @return ArchiveEntry[]
			 */
			public function provide( Settings $settings ): array {
				unset( $settings );
				return array( new ArchiveEntry( $this->url ) );
			}
		};
	}

	/**
	 * News mode routes to the News provider.
	 *
	 * @return void
	 */
	public function test_news_mode_uses_news_provider(): void {
		$provider = new ModeEntryProvider( $this->stub( 'news' ), $this->stub( 'blog' ) );

		$entries = $provider->provide( Settings::from_array( array( 'mode' => 'news' ) ) );

		$this->assertSame( 'news', $entries[0]->url() );
	}

	/**
	 * Blog mode routes to the Blog provider.
	 *
	 * @return void
	 */
	public function test_blog_mode_uses_blog_provider(): void {
		$provider = new ModeEntryProvider( $this->stub( 'news' ), $this->stub( 'blog' ) );

		$entries = $provider->provide( Settings::from_array( array( 'mode' => 'blog' ) ) );

		$this->assertSame( 'blog', $entries[0]->url() );
	}
}
