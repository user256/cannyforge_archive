<?php
/**
 * Tests for the whole-database content index provider's query building.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Core\Archive\ContentIndexProvider;
use PHPUnit\Framework\TestCase;

/**
 * The query args target all published posts, paginated, with the right filters.
 */
class ContentIndexProviderTest extends TestCase {
	/**
	 * Build args for a given query.
	 *
	 * @param ContentQuery $query The request.
	 * @return array<string, mixed>
	 */
	private function args( ContentQuery $query ): array {
		return ( new ContentIndexProvider() )->build_query_args( $query );
	}

	/**
	 * The base query is published posts, newest first, paginated, with totals.
	 *
	 * @return void
	 */
	public function test_base_query_targets_all_published_posts(): void {
		$args = $this->args( new ContentQuery( '', '', '', '', '', 2, 25 ) );

		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 'post', $args['post_type'] );
		$this->assertSame( 'date', $args['orderby'] );
		$this->assertSame( 'DESC', $args['order'] );
		$this->assertSame( 25, $args['posts_per_page'] );
		$this->assertSame( 2, $args['paged'] );
		// No date window: old content is reachable, unlike the News provider.
		$this->assertArrayNotHasKey( 'date_query', $args );
	}

	/**
	 * A search term maps to WP_Query's `s`.
	 *
	 * @return void
	 */
	public function test_search_term_maps_to_s(): void {
		$args = $this->args( new ContentQuery( 'crawl budget' ) );

		$this->assertSame( 'crawl budget', $args['s'] );
	}

	/**
	 * A slug-like category becomes a slug clause; a name-like one a name clause.
	 *
	 * @return void
	 */
	public function test_category_slug_vs_name_clause(): void {
		$slug = $this->args( new ContentQuery( '', 'industry-news' ) );
		$this->assertSame( 'category', $slug['tax_query'][0]['taxonomy'] );
		$this->assertSame( 'slug', $slug['tax_query'][0]['field'] );
		$this->assertSame( 'industry-news', $slug['tax_query'][0]['terms'] );

		$name = $this->args( new ContentQuery( '', 'Industry News' ) );
		$this->assertSame( 'name', $name['tax_query'][0]['field'] );
	}

	/**
	 * Category and tag together combine with AND.
	 *
	 * @return void
	 */
	public function test_category_and_tag_combine_with_and(): void {
		$args = $this->args( new ContentQuery( '', 'news', 'tennis' ) );

		$this->assertSame( 'AND', $args['tax_query']['relation'] );
		$this->assertCount( 3, $args['tax_query'] ); // two clauses + relation.
	}

	/**
	 * The author filter maps to author_name.
	 *
	 * @return void
	 */
	public function test_author_maps_to_author_name(): void {
		$args = $this->args( new ContentQuery( '', '', '', 'jane-doe' ) );

		$this->assertSame( 'jane-doe', $args['author_name'] );
	}

	/**
	 * A month filter maps to a year+month date clause.
	 *
	 * @return void
	 */
	public function test_month_maps_to_date_query(): void {
		$args = $this->args( new ContentQuery( '', '', '', '', '2024-03' ) );

		$this->assertSame( 2024, $args['date_query'][0]['year'] );
		$this->assertSame( 3, $args['date_query'][0]['month'] );
	}
}
