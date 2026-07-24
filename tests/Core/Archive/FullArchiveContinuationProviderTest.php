<?php
/**
 * Tests for the full archive continuation query shape.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\ContentSelection;
use CannyForge\Archive\Core\Archive\FullArchiveContinuationProvider;
use CannyForge\Archive\Core\Archive\FullArchiveQueryArgsBuilder;
use PHPUnit\Framework\TestCase;

/** Tests continuation query shape, ID exclusion and page boundaries. */
final class FullArchiveContinuationProviderTest extends TestCase {
	public function test_query_is_bounded_and_deterministic(): void {
		$args = ( new FullArchiveContinuationProvider() )->build_continuation_query_args( new ContentSelection(), array( 17, 23 ), 3 );

		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 'post', $args['post_type'] );
		$this->assertSame(
			array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			$args['orderby']
		);
		$this->assertSame( 50, $args['posts_per_page'] );
		$this->assertSame( 3, $args['paged'] );
		$this->assertSame( array( 17, 23 ), $args['post__not_in'] );
		$this->assertTrue( $args['no_found_rows'] );
	}

	/**
	 * Count queries fetch one ID and derive the total from found_posts.
	 *
	 * @return void
	 */
	public function test_count_query_is_one_row_and_ids_only(): void {
		$provider = new FullArchiveContinuationProvider();
		$args     = $provider->build_continuation_count_args( new ContentSelection(), array( 17, 23 ) );

		$this->assertSame( 1, $args['posts_per_page'] );
		$this->assertSame( 'ids', $args['fields'] );
		$this->assertFalse( $args['no_found_rows'] );
		$this->assertArrayNotHasKey( 'paged', $args );
		$this->assertNotSame( -1, $args['posts_per_page'] );
	}

	/**
	 * Page one checks only for one remaining ID and does not calculate totals.
	 *
	 * @return void
	 */
	public function test_existence_query_is_one_row_without_found_rows(): void {
		$args = ( new FullArchiveQueryArgsBuilder() )->existence(
			new ContentSelection(),
			array( 0, 17, 17, 23 )
		);

		$this->assertSame( 1, $args['posts_per_page'] );
		$this->assertSame( 'ids', $args['fields'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertSame( array( 17, 23 ), $args['post__not_in'] );
		$this->assertArrayNotHasKey( 'paged', $args );
	}

	/**
	 * Selection rules are applied in the database before paging.
	 *
	 * @return void
	 */
	public function test_selection_rules_become_database_constraints(): void {
		$selection = new ContentSelection(
			array( 'News' ),
			array( 'Featured' ),
			array( 'Internal' ),
			array( 'Spoiler' ),
			true
		);
		$args      = ( new FullArchiveContinuationProvider() )->build_continuation_query_args( $selection, array(), 1 );

		$this->assertSame( 'AND', $args['tax_query']['relation'] );
		$this->assertSame( 'OR', $args['tax_query'][0]['relation'] );
		$this->assertSame( 'category', $args['tax_query'][0][0]['taxonomy'] );
		$this->assertSame( array( 'News' ), $args['tax_query'][0][0]['terms'] );
		$this->assertSame( 'post_tag', $args['tax_query'][0][1]['taxonomy'] );
		$this->assertSame( array( 'Featured' ), $args['tax_query'][0][1]['terms'] );
		$this->assertSame( 'category', $args['tax_query'][1]['taxonomy'] );
		$this->assertSame( 'NOT IN', $args['tax_query'][1]['operator'] );
		$this->assertSame( array( 'Internal' ), $args['tax_query'][1]['terms'] );
		$this->assertSame( 'post_tag', $args['tax_query'][2]['taxonomy'] );
		$this->assertSame( array( 'Spoiler' ), $args['tax_query'][2]['terms'] );
		$this->assertSame( 'AND', $args['meta_query']['relation'] );
	}
}
