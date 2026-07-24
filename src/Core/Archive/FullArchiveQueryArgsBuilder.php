<?php
/**
 * Query arguments for the server-rendered full archive continuation.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\ContentSelection;

/**
 * Builds bounded page, count, and existence queries for archive continuation.
 *
 * Selection rules are expressed in WordPress query arguments so the database
 * filters candidates before PHP receives them. This keeps work proportional
 * to one result page instead of the total number of published posts.
 */
final class FullArchiveQueryArgsBuilder {
	/** Entries per server-rendered continuation page. */
	public const PER_PAGE = 50;

	/**
	 * Build the query for one continuation page.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Local post IDs already shown on page one.
	 * @param int              $page         Continuation page number, one-based.
	 * @return array<string, mixed>
	 */
	public function page( ContentSelection $selection, array $excluded_ids, int $page ): array {
		return $this->base( $selection, $excluded_ids ) + array(
			'posts_per_page' => self::PER_PAGE,
			'paged'          => max( 1, $page ),
			'no_found_rows'  => true,
		);
	}

	/**
	 * Build the one-row query used to obtain the exact eligible total.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Local post IDs already shown on page one.
	 * @return array<string, mixed>
	 */
	public function count( ContentSelection $selection, array $excluded_ids ): array {
		return $this->base( $selection, $excluded_ids ) + array(
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
		);
	}

	/**
	 * Build the one-row query used to decide whether page two should be linked.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Local post IDs already shown on page one.
	 * @return array<string, mixed>
	 */
	public function existence( ContentSelection $selection, array $excluded_ids ): array {
		return $this->base( $selection, $excluded_ids ) + array(
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		);
	}

	/**
	 * Common deterministic query and database-level selection constraints.
	 *
	 * @param ContentSelection $selection    Existing content-selection rules.
	 * @param int[]            $excluded_ids Local post IDs already shown on page one.
	 * @return array<string, mixed>
	 */
	private function base( ContentSelection $selection, array $excluded_ids ): array {
		$args = array(
			'post_status'         => 'publish',
			'post_type'           => 'post',
			'orderby'             => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'ignore_sticky_posts' => true,
		);

		$excluded_ids = array_values( array_unique( array_filter( array_map( 'absint', $excluded_ids ) ) ) );
		if ( array() !== $excluded_ids ) {
			$args['post__not_in'] = $excluded_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- finite page-one membership must be excluded by stable ID; the result and count queries remain bounded.
		}

		$tax_query = $this->tax_query( $selection );
		if ( array() !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- administrator-selected archive constraints must be applied before pagination.
		}

		$meta_query = $this->meta_query( $selection );
		if ( array() !== $meta_query ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- optional noindex exclusion must be applied before counting/pagination.
		}

		return $args;
	}

	/**
	 * Translate the selector's taxonomy-specific term semantics into WP_Tax_Query.
	 *
	 * @param ContentSelection $selection Existing content-selection rules.
	 * @return array<int|string, mixed>
	 */
	private function tax_query( ContentSelection $selection ): array {
		$include_categories = $this->terms( $selection->include_categories() );
		$include_tags       = $this->terms( $selection->include_tags() );
		$exclude_categories = $this->terms( $selection->exclude_categories() );
		$exclude_tags       = $this->terms( $selection->exclude_tags() );
		$query              = array( 'relation' => 'AND' );
		$include_clauses    = array();

		if ( array() !== $include_categories ) {
			$include_clauses[] = $this->term_clause( 'category', $include_categories, 'IN' );
		}
		if ( array() !== $include_tags ) {
			$include_clauses[] = $this->term_clause( 'post_tag', $include_tags, 'IN' );
		}

		if ( 1 === count( $include_clauses ) ) {
			$query[] = $include_clauses[0];
		} elseif ( 1 < count( $include_clauses ) ) {
			$query[] = array_merge( array( 'relation' => 'OR' ), $include_clauses );
		}

		if ( array() !== $exclude_categories ) {
			$query[] = $this->term_clause( 'category', $exclude_categories, 'NOT IN' );
		}
		if ( array() !== $exclude_tags ) {
			$query[] = $this->term_clause( 'post_tag', $exclude_tags, 'NOT IN' );
		}

		return count( $query ) > 1 ? $query : array();
	}

	/**
	 * Build one taxonomy clause using names selected in the settings UI.
	 *
	 * @param string   $taxonomy Taxonomy slug.
	 * @param string[] $terms    Selected term names.
	 * @param string   $operator IN or NOT IN.
	 * @return array<string, mixed>
	 */
	private function term_clause( string $taxonomy, array $terms, string $operator ): array {
		return array(
			'taxonomy' => $taxonomy,
			'field'    => 'name',
			'terms'    => $terms,
			'operator' => $operator,
		);
	}

	/**
	 * Translate supported SEO-plugin noindex flags into WP_Meta_Query.
	 *
	 * @param ContentSelection $selection Existing content-selection rules.
	 * @return array<int|string, mixed>
	 */
	private function meta_query( ContentSelection $selection ): array {
		if ( ! $selection->exclude_noindex() ) {
			return array();
		}

		return array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_yoast_wpseo_meta-robots-noindex',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_yoast_wpseo_meta-robots-noindex',
					'value'   => '1',
					'compare' => '!=',
				),
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'rank_math_robots',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'rank_math_robots',
					'value'   => '"noindex"',
					'compare' => 'NOT LIKE',
				),
			),
		);
	}

	/**
	 * Trim, de-duplicate, and re-index term names for `tax_query` `field => name`.
	 *
	 * Empty labels are dropped. Matching against post terms is case-insensitive
	 * under typical MySQL collations, matching {@see TermLabelMatcher}.
	 *
	 * @param string[] $terms Raw term names.
	 * @return string[]
	 */
	private function terms( array $terms ): array {
		$clean = array();
		foreach ( $terms as $term ) {
			$trimmed = trim( $term );
			if ( '' === $trimmed ) {
				continue;
			}
			$key = TermLabelMatcher::normalize( $trimmed );
			if ( '' === $key || isset( $clean[ $key ] ) ) {
				continue;
			}
			$clean[ $key ] = $trimmed;
		}

		return array_values( $clean );
	}
}
