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

	/**
	 * Tier 1 wins: when posts have comments, the comment-ordered IDs are chosen.
	 *
	 * @return void
	 */
	public function test_fallback_prefers_comments_when_present(): void {
		$ids = ( new BlogEntryProvider() )->select_fallback_ids(
			array(),
			array( 7, 3, 9 ),
			true,
			array( 100, 200 ),
			array( 1, 2, 3 ),
			100
		);

		$this->assertSame( array( 7, 3, 9 ), $ids );
	}

	/**
	 * The comment tier is gated: with no commented post, comment order is ignored
	 * even though IDs exist, and Jetpack (tier 2) is used instead.
	 *
	 * @return void
	 */
	public function test_fallback_skips_comments_when_none_commented(): void {
		$ids = ( new BlogEntryProvider() )->select_fallback_ids(
			array(),
			array( 7, 3, 9 ),
			false,
			array( 100, 200 ),
			array( 1, 2, 3 ),
			100
		);

		$this->assertSame( array( 100, 200 ), $ids );
	}

	/**
	 * Tier 3: with no comments and no Jetpack data, newest is the floor.
	 *
	 * @return void
	 */
	public function test_fallback_uses_newest_as_floor(): void {
		$ids = ( new BlogEntryProvider() )->select_fallback_ids(
			array(),
			array(),
			false,
			array(),
			array( 1, 2, 3 ),
			100
		);

		$this->assertSame( array( 1, 2, 3 ), $ids );
	}

	/**
	 * The chosen tier is de-duplicated, stripped of non-positive IDs, and capped.
	 *
	 * @return void
	 */
	public function test_fallback_dedupes_and_caps(): void {
		$ids = ( new BlogEntryProvider() )->select_fallback_ids(
			array(),
			array( 5, 5, 0, 8, 8, 11 ),
			true,
			array(),
			array(),
			2
		);

		$this->assertSame( array( 5, 8 ), $ids );
	}

	/**
	 * Google/Search Console cached IDs outrank the comment/Jetpack/newest tiers.
	 *
	 * @return void
	 */
	public function test_fallback_prefers_google_when_available(): void {
		$ids = ( new BlogEntryProvider() )->select_fallback_ids(
			array( 42, 7 ),
			array( 7, 3, 9 ),
			true,
			array( 100, 200 ),
			array( 1, 2, 3 ),
			100
		);

		$this->assertSame( array( 42, 7 ), $ids );
	}
}
