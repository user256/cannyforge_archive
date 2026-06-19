<?php
/**
 * Tests for the ContentQuery value object.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Contracts\Archive;

use CannyForge\Archive\Contracts\Archive\ContentQuery;
use PHPUnit\Framework\TestCase;

/**
 * Normalisation, clamping, and the active-query distinction.
 */
class ContentQueryTest extends TestCase {
	/**
	 * A query with no term and no filter is inactive (promoted default view).
	 *
	 * @return void
	 */
	public function test_empty_query_is_inactive(): void {
		$this->assertFalse( ( new ContentQuery() )->is_active() );
	}

	/**
	 * Any single constraint makes the query active.
	 *
	 * @return void
	 */
	public function test_any_constraint_is_active(): void {
		$this->assertTrue( ( new ContentQuery( 'cats' ) )->is_active() );
		$this->assertTrue( ( new ContentQuery( '', 'news' ) )->is_active() );
		$this->assertTrue( ( new ContentQuery( '', '', 'tennis' ) )->is_active() );
		$this->assertTrue( ( new ContentQuery( '', '', '', 'jane' ) )->is_active() );
		$this->assertTrue( ( new ContentQuery( '', '', '', '', '2026-06' ) )->is_active() );
	}

	/**
	 * Whitespace-only fields collapse to empty.
	 *
	 * @return void
	 */
	public function test_trims_fields(): void {
		$query = new ContentQuery( '   ', '  ' );

		$this->assertSame( '', $query->search() );
		$this->assertFalse( $query->is_active() );
	}

	/**
	 * A malformed month is discarded; a valid Y-m is kept.
	 *
	 * @return void
	 */
	public function test_month_must_be_year_month(): void {
		$this->assertSame( '', ( new ContentQuery( '', '', '', '', 'June 2026' ) )->month() );
		$this->assertSame( '2026-06', ( new ContentQuery( '', '', '', '', '2026-06' ) )->month() );
	}

	/**
	 * Page is at least 1; per_page is clamped to the bounded range.
	 *
	 * @return void
	 */
	public function test_pagination_is_bounded(): void {
		$low = new ContentQuery( '', '', '', '', '', -5, 0 );
		$this->assertSame( 1, $low->page() );
		$this->assertSame( 20, $low->per_page() );

		$high = new ContentQuery( '', '', '', '', '', 3, 9999 );
		$this->assertSame( 3, $high->page() );
		$this->assertSame( ContentQuery::MAX_PER_PAGE, $high->per_page() );
	}

	/**
	 * The from_array() factory coerces and defaults each field.
	 *
	 * @return void
	 */
	public function test_from_array(): void {
		$query = ContentQuery::from_array(
			array(
				'search'   => 'pagination',
				'category' => 'news',
				'page'     => '2',
				'per_page' => '15',
			)
		);

		$this->assertSame( 'pagination', $query->search() );
		$this->assertSame( 'news', $query->category() );
		$this->assertSame( 2, $query->page() );
		$this->assertSame( 15, $query->per_page() );
	}
}
