<?php
/**
 * Tests for the ContentPage result value object.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Contracts\Archive;

use CannyForge\Archive\Contracts\Archive\ContentPage;
use PHPUnit\Framework\TestCase;

/**
 * Pagination maths: page counts and next/prev availability.
 */
class ContentPageTest extends TestCase {
	/**
	 * Total pages rounds up; a partial final page still counts.
	 *
	 * @return void
	 */
	public function test_total_pages_rounds_up(): void {
		$page = new ContentPage( array(), 45, 1, 20 );

		$this->assertSame( 3, $page->total_pages() );
	}

	/**
	 * An empty result set still reports one page.
	 *
	 * @return void
	 */
	public function test_empty_has_one_page(): void {
		$page = new ContentPage( array(), 0, 1, 20 );

		$this->assertSame( 1, $page->total_pages() );
		$this->assertFalse( $page->has_next() );
		$this->assertFalse( $page->has_prev() );
	}

	/**
	 * The first page has a next but no previous.
	 *
	 * @return void
	 */
	public function test_first_page_navigation(): void {
		$page = new ContentPage( array(), 60, 1, 20 );

		$this->assertTrue( $page->has_next() );
		$this->assertFalse( $page->has_prev() );
	}

	/**
	 * The last page has a previous but no next.
	 *
	 * @return void
	 */
	public function test_last_page_navigation(): void {
		$page = new ContentPage( array(), 60, 3, 20 );

		$this->assertFalse( $page->has_next() );
		$this->assertTrue( $page->has_prev() );
	}
}
