<?php
/**
 * Tests for the full archive continuation query shape.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Core\Archive\FullArchiveContinuationProvider;
use PHPUnit\Framework\TestCase;

/** Tests continuation query shape, ID exclusion and page boundaries. */
final class FullArchiveContinuationProviderTest extends TestCase {
	public function test_query_is_bounded_and_deterministic(): void {
		$args = ( new FullArchiveContinuationProvider() )->build_continuation_query_args( array( 17, 23 ), 3 );

		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 'post', $args['post_type'] );
		$this->assertSame(
			array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			$args['orderby']
		);
		$this->assertSame( 200, $args['posts_per_page'] );
		$this->assertSame( 3, $args['paged'] );
		$this->assertSame( array( 17, 23 ), $args['post__not_in'] );
	}

	/**
	 * Page-one entries are excluded by their local IDs, while an external
	 * curated URL has no local ID and therefore cannot suppress a post.
	 *
	 * @return void
	 */
	public function test_page_entries_excludes_page_one_ids_and_has_no_boundary_duplicates(): void {
		$provider = new FullArchiveContinuationProvider();
		$eligible = array();
		for ( $id = 1; $id <= 102; ++$id ) {
			$eligible[] = new ArchiveEntry( 'https://site.test/' . $id . '/', (string) $id, '', '', array(), array(), '', '2026-01-01', false, $id );
		}
		$page_one = array(
			$eligible[0],
			new ArchiveEntry( 'https://external.example/curated/', 'External curated URL' ),
		);

		$first      = $provider->page_entries( $eligible, $page_one, 1 );
		$second     = $provider->page_entries( $eligible, $page_one, 2 );
		$third      = $provider->page_entries( $eligible, $page_one, 3 );
		$first_ids  = array_map( static fn ( ArchiveEntry $entry ): int => $entry->local_post_id(), $first->entries() );
		$second_ids = array_map( static fn ( ArchiveEntry $entry ): int => $entry->local_post_id(), $second->entries() );
		$third_ids  = array_map( static fn ( ArchiveEntry $entry ): int => $entry->local_post_id(), $third->entries() );

		$this->assertSame( 101, $first->total() );
		$this->assertSame( range( 2, 51 ), $first_ids );
		$this->assertSame( range( 52, 101 ), $second_ids );
		$this->assertSame( array( 102 ), $third_ids );
		$this->assertSame( array(), array_values( array_intersect( $first_ids, $second_ids ) ) );
	}
}
