<?php
/**
 * Whole-database filter option sources for the archive controls.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

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
		$found = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$months = array();
		foreach ( $found as $post_id ) {
			$ymd = get_the_date( 'Y-m', (int) $post_id );
			if ( is_string( $ymd ) && '' !== $ymd ) {
				$months[ $ymd ] = true;
			}
		}

		$keys = array_keys( $months );
		rsort( $keys );

		$options = array();
		foreach ( $keys as $ym ) {
			$options[] = array(
				'value' => $ym,
				'label' => $this->human_month( $ym ),
			);
		}

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
