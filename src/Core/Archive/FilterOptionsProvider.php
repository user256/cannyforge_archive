<?php
/**
 * Whole-database filter option sources for the archive controls.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies the filter dropdown options (categories, tags, authors, months) drawn
 * from the *entire* content database rather than the promoted entry set
 * (ticket 301).
 *
 * Sprint 1 derived these options from the rendered entries, so a filter could
 * only ever surface what was already shown. To let users discover all content,
 * the options must reflect the whole site — that is this class's only job.
 * Each value is an `[value, label]` pair: `value` is the slug (or `Y-m`) the
 * front-end sends back as a {@see \CannyForge\Archive\Contracts\Archive\ContentQuery}
 * filter; `label` is what the user sees.
 */
final class FilterOptionsProvider {
	/** Object-cache group for whole-database option lists. */
	private const CACHE_GROUP = 'cannyforge_archive';

	/**
	 * Distinct categories across the site as slug→name pairs.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function categories(): array {
		return $this->terms( 'category' );
	}

	/**
	 * Distinct tags across the site as slug→name pairs.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function tags(): array {
		return $this->terms( 'post_tag' );
	}

	/**
	 * Authors who have published posts, as nicename→display-name pairs.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function authors(): array {
		$users = get_users(
			array(
				'has_published_posts' => array( 'post' ),
				'orderby'             => 'display_name',
				'order'               => 'ASC',
			)
		);

		$options = array();
		foreach ( $users as $user ) {
			$options[] = array(
				'value' => (string) $user->user_nicename,
				'label' => (string) $user->display_name,
			);
		}

		return $options;
	}

	/**
	 * Distinct publication months across the site, newest first, as `Y-m` pairs.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public function months(): array {
		$cache_key = 'filter_months_' . wp_cache_get_last_changed( 'posts' );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		if ( ! $wpdb instanceof \wpdb ) {
			return array();
		}

		$query = $wpdb->prepare(
			'SELECT DISTINCT DATE_FORMAT(post_date, %s) AS archive_month
			FROM %i
			WHERE post_type = %s AND post_status = %s
			ORDER BY archive_month DESC',
			'%Y-%m',
			$wpdb->posts,
			'post',
			'publish'
		);

		$found  = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- DISTINCT is not expressible through WP_Query; $query is prepared above and keyed to the core posts last-changed cache token.
		$months = array();
		foreach ( $found as $year_month ) {
			if ( is_string( $year_month ) && 1 === preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])$/', $year_month ) ) {
				$months[] = $year_month;
			}
		}

		$options = array();
		foreach ( array_values( array_unique( $months ) ) as $ym ) {
			$options[] = array(
				'value' => $ym,
				'label' => $this->human_month( $ym ),
			);
		}

		wp_cache_set( $cache_key, $options, self::CACHE_GROUP );

		return $options;
	}

	/**
	 * Distinct terms in a taxonomy as slug→name pairs, alphabetical by name.
	 *
	 * @param string $taxonomy The taxonomy.
	 * @return array<int, array{value: string, label: string}>
	 */
	private function terms( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$options = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$options[] = array(
					'value' => (string) $term->slug,
					'label' => (string) $term->name,
				);
			}
		}

		return $options;
	}

	/**
	 * Format a `Y-m` month into a human label (e.g. "Jun 2026").
	 *
	 * @param string $ym The month.
	 * @return string
	 */
	private function human_month( string $ym ): string {
		$parsed = \DateTimeImmutable::createFromFormat( 'Y-m-d', $ym . '-01' );

		return false === $parsed ? $ym : $parsed->format( 'M Y' );
	}
}
