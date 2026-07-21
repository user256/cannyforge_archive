<?php
/**
 * Whole-database content index provider (search / filter / paginate).
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ContentPage;
use CannyForge\Archive\Contracts\Archive\ContentQuery;

/**
 * Queries the *entire* published content database for the archive's search and
 * filter navigation (ticket 301).
 *
 * The HTML-sitemap page promotes a bounded set (recent window / top URLs). This
 * provider is the other half: when a user searches or filters, it runs a
 * paginated `WP_Query` across all content so genuinely old / non-promoted posts
 * are findable. As with {@see NewsEntryProvider}, the query-building logic lives
 * in a pure method ({@see self::build_query_args()}) unit-tested without
 * WordPress; executing the query and option sourcing are isolated so the
 * selection logic needs no WordPress runtime to test.
 *
 * Not `final`: {@see \CannyForge\Archive\Tests\FakeContentIndexProvider} extends
 * it to stand in for `provide()` in the endpoint's unit tests (ticket 601) —
 * `provide()` instantiates `\WP_Query` directly, which does not exist in the
 * shim-only unit-test runtime, so the test double overrides it instead of
 * calling through.
 */
class ContentIndexProvider {
	/**
	 * Build the `WP_Query` args for a content query.
	 *
	 * Pure and deterministic given the query: published posts only, newest first,
	 * paginated, with optional search/category/tag/author/month constraints. The
	 * found-rows count is required (the front-end needs the total to paginate).
	 *
	 * @param ContentQuery $query The request.
	 * @return array<string, mixed>
	 */
	public function build_query_args( ContentQuery $query ): array {
		$args = array(
			'post_status'         => 'publish',
			'post_type'           => 'post',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => $query->per_page(),
			'paged'               => $query->page(),
			'ignore_sticky_posts' => true,
		);

		if ( '' !== $query->search() ) {
			$args['s'] = $query->search();
		}

		$tax_query = $this->tax_query( $query );
		if ( array() !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- archive navigation is intentionally taxonomy-driven.
		}

		if ( '' !== $query->author() ) {
			$args['author_name'] = $query->author();
		}

		$date_query = $this->date_query( $query );
		if ( array() !== $date_query ) {
			$args['date_query'] = $date_query;
		}

		return $args;
	}

	/**
	 * Build the taxonomy constraints (category / tag) for the query.
	 *
	 * Each term is matched by slug OR name so the caller can pass either; the
	 * clauses combine with AND when both a category and tag are selected.
	 *
	 * @param ContentQuery $query The request.
	 * @return array<int|string, mixed>
	 */
	private function tax_query( ContentQuery $query ): array {
		$clauses = array();

		if ( '' !== $query->category() ) {
			$clauses[] = $this->term_clause( 'category', $query->category() );
		}

		if ( '' !== $query->tag() ) {
			$clauses[] = $this->term_clause( 'post_tag', $query->tag() );
		}

		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'AND';
		}

		return $clauses;
	}

	/**
	 * A single slug-or-name term clause for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy.
	 * @param string $value    The slug or name.
	 * @return array<string, mixed>
	 */
	private function term_clause( string $taxonomy, string $value ): array {
		return array(
			'taxonomy' => $taxonomy,
			'field'    => $this->looks_like_slug( $value ) ? 'slug' : 'name',
			'terms'    => $value,
		);
	}

	/**
	 * Heuristic: a value with no spaces and only slug characters is a slug.
	 *
	 * @param string $value The value.
	 * @return bool
	 */
	private function looks_like_slug( string $value ): bool {
		return 1 === preg_match( '/^[a-z0-9._-]+$/', $value );
	}

	/**
	 * Build the date constraint from a `Y-m` month, or [] when none.
	 *
	 * @param ContentQuery $query The request.
	 * @return array<int, array<string, int>>
	 */
	private function date_query( ContentQuery $query ): array {
		if ( '' === $query->month() ) {
			return array();
		}

		$parts = explode( '-', $query->month() );

		return array(
			array(
				'year'  => (int) $parts[0],
				'month' => (int) $parts[1],
			),
		);
	}

	/**
	 * Run the query and return the matching page of entries plus the total.
	 *
	 * A first lightweight query establishes the true match count, so the requested
	 * page can be clamped to the last valid page. Without this, WordPress reports
	 * `found_posts = 0` for any page past the end (see {@see self::count()}), which
	 * would make an out-of-range request look like "no results" rather than the
	 * tail of a large set.
	 *
	 * @param ContentQuery $query The request.
	 * @return ContentPage
	 */
	public function provide( ContentQuery $query ): ContentPage {
		$total    = $this->count( $query );
		$per_page = $query->per_page();
		$pages    = (int) max( 1, (int) ceil( $total / $per_page ) );
		$page     = min( $query->page(), $pages );

		if ( 0 === $total ) {
			return new ContentPage( array(), 0, $query->page(), $per_page );
		}

		$args          = $this->build_query_args( $query );
		$args['paged'] = $page;

		$wp_query = new \WP_Query( $args );
		$entries  = array();

		foreach ( $wp_query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$entries[] = $this->map_post( $post );
			}
		}

		return new ContentPage( $entries, $total, $page, $per_page );
	}

	/**
	 * Count the posts matching a query, cheaply (IDs only, no found-rows pass).
	 *
	 * @param ContentQuery $query The request.
	 * @return int
	 */
	private function count( ContentQuery $query ): int {
		$args                   = $this->build_query_args( $query );
		$args['fields']         = 'ids';
		$args['posts_per_page'] = -1;
		$args['no_found_rows']  = true;
		unset( $args['paged'] );

		$wp_query = new \WP_Query( $args );

		return count( $wp_query->posts );
	}

	/**
	 * Map a single WordPress post to an archive entry.
	 *
	 * @param \WP_Post $post The post.
	 * @return ArchiveEntry
	 */
	private function map_post( \WP_Post $post ): ArchiveEntry {
		$date = get_the_date( 'Y-m-d', $post );

		return new ArchiveEntry(
			(string) get_permalink( $post ),
			get_the_title( $post ),
			trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) get_the_excerpt( $post ) ) ) ) ?? '' ),
			(string) get_the_post_thumbnail_url( $post ),
			$this->term_names( $post->ID, 'category' ),
			$this->term_names( $post->ID, 'post_tag' ),
			get_the_author_meta( 'display_name', (int) $post->post_author ),
			is_string( $date ) ? $date : ''
		);
	}

	/**
	 * Fetch term names for a post and taxonomy as a clean string list.
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $taxonomy The taxonomy.
	 * @return string[]
	 */
	private function term_names( int $post_id, string $taxonomy ): array {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_values( array_filter( $terms, 'is_string' ) );
	}
}
