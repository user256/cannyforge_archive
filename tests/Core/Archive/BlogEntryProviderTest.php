<?php
/**
 * Tests for the Blog-mode entry provider's URL selection.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\BlogEntryProvider;
use PHPUnit\Framework\TestCase;

/**
 * Selection keeps valid URLs, de-duplicates, and respects the configured cap.
 */
class BlogEntryProviderTest extends TestCase {
	/**
	 * Select the URLs the provider would resolve for a given URL list and cap.
	 *
	 * @param string[] $urls Raw URL list.
	 * @param int      $cap  Maximum URLs to include.
	 * @return string[]
	 */
	private function select( array $urls, int $cap = 100 ): array {
		$settings = Settings::from_array(
			array(
				'mode'          => 'blog',
				'blog_max_urls' => $cap,
				'blog_urls'     => $urls,
			)
		);

		return ( new BlogEntryProvider() )->select_urls( $settings );
	}

	/**
	 * Invalid and non-HTTP(S) lines are dropped, leaving only resolvable URLs.
	 *
	 * @return void
	 */
	public function test_drops_invalid_and_non_http_urls(): void {
		$selected = $this->select(
			array(
				'https://example.test/features/',
				'not-a-url',
				'ftp://example.test/file',
				'http://example.test/ufo-sighting/',
				'   ',
			)
		);

		$this->assertSame(
			array(
				'https://example.test/features/',
				'http://example.test/ufo-sighting/',
			),
			$selected
		);
	}

	/**
	 * Duplicate URLs collapse to one, keeping first-seen order.
	 *
	 * @return void
	 */
	public function test_de_duplicates_urls(): void {
		$selected = $this->select(
			array(
				'https://example.test/a/',
				'https://example.test/b/',
				'https://example.test/a/',
			)
		);

		$this->assertSame(
			array(
				'https://example.test/a/',
				'https://example.test/b/',
			),
			$selected
		);
	}

	/**
	 * The selection never exceeds the configured cap.
	 *
	 * @return void
	 */
	public function test_caps_the_list(): void {
		$urls = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$urls[] = sprintf( 'https://example.test/post-%d/', $i );
		}

		$selected = $this->select( $urls, 3 );

		$this->assertCount( 3, $selected );
		$this->assertSame( 'https://example.test/post-0/', $selected[0] );
		$this->assertSame( 'https://example.test/post-2/', $selected[2] );
	}

	/**
	 * An empty / all-invalid list yields no entries.
	 *
	 * @return void
	 */
	public function test_empty_when_nothing_valid(): void {
		$this->assertSame( array(), $this->select( array( 'nope', '', '   ' ) ) );
	}
}
