<?php
/**
 * Tests for the shortened pagination renderer.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Pagination;

use CannyForge\Archive\Core\Pagination\PaginationRenderer;
use PHPUnit\Framework\TestCase;

/**
 * The renderer caps page links at the limit and always links to the archive.
 */
class PaginationRendererTest extends TestCase {
	/**
	 * Map a page number to a deterministic URL for assertions.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	private function pageUrl( int $page ): string {
		return 'https://site.test/page/' . $page . '/';
	}

	/**
	 * Render with the given limit/total at page 1, linking to a fixed archive.
	 *
	 * @param int $limit Pagination limit.
	 * @param int $total Total pages.
	 * @return string
	 */
	private function render( int $limit, int $total ): string {
		return ( new PaginationRenderer() )->render(
			1,
			$total,
			$limit,
			'https://site.test/archive/',
			'View Archive',
			fn ( int $page ): string => $this->pageUrl( $page )
		);
	}

	/**
	 * The number of page links never exceeds the configured limit.
	 *
	 * @return void
	 */
	public function test_caps_page_links_at_the_limit(): void {
		$markup = $this->render( 3, 50 );

		$this->assertSame( 3, substr_count( $markup, 'cannyforge-pagination__page' ) );
	}

	/**
	 * The visible count never exceeds the pages that actually exist.
	 *
	 * @return void
	 */
	public function test_never_shows_more_than_total_pages(): void {
		$markup = $this->render( 9, 2 );

		$this->assertSame( 2, substr_count( $markup, 'cannyforge-pagination__page' ) );
	}

	/**
	 * The default limit of 1 shows a single page link.
	 *
	 * @return void
	 */
	public function test_default_limit_shows_one_page(): void {
		$markup = $this->render( 1, 20 );

		$this->assertSame( 1, substr_count( $markup, 'cannyforge-pagination__page' ) );
	}

	/**
	 * The deep tail is never emitted — page 9's URL is absent beyond the limit.
	 *
	 * @return void
	 */
	public function test_does_not_emit_deep_tail(): void {
		$markup = $this->render( 3, 50 );

		$this->assertStringNotContainsString( $this->pageUrl( 9 ), $markup );
		$this->assertStringNotContainsString( $this->pageUrl( 4 ), $markup );
	}

	/**
	 * The archive link target is the URL passed in.
	 *
	 * @return void
	 */
	public function test_links_to_the_archive_url(): void {
		$markup = $this->render( 1, 20 );

		$this->assertStringContainsString( 'href="https://site.test/archive/"', $markup );
		$this->assertStringContainsString( 'cannyforge-pagination__archive', $markup );
		$this->assertStringContainsString( 'View Archive', $markup );
	}

	/**
	 * A configured destination override is used verbatim for the archive link.
	 *
	 * @return void
	 */
	public function test_honours_a_configured_archive_url(): void {
		$markup = ( new PaginationRenderer() )->render(
			1,
			20,
			1,
			'https://elsewhere.test/all-stories/',
			'View Archive',
			fn ( int $page ): string => $this->pageUrl( $page )
		);

		$this->assertStringContainsString( 'href="https://elsewhere.test/all-stories/"', $markup );
	}

	/**
	 * The current page is marked rather than linked.
	 *
	 * @return void
	 */
	public function test_marks_the_current_page(): void {
		$markup = ( new PaginationRenderer() )->render(
			2,
			20,
			3,
			'https://site.test/archive/',
			'View Archive',
			fn ( int $page ): string => $this->pageUrl( $page )
		);

		$this->assertStringContainsString( 'is-current', $markup );
		$this->assertStringContainsString( 'aria-current="page"', $markup );
	}

	/**
	 * With no pages and no archive URL, nothing is rendered.
	 *
	 * @return void
	 */
	public function test_empty_when_nothing_to_show(): void {
		$markup = ( new PaginationRenderer() )->render(
			1,
			0,
			1,
			'',
			'View Archive',
			fn ( int $page ): string => $this->pageUrl( $page )
		);

		$this->assertSame( '', $markup );
	}
}
