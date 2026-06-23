<?php
/**
 * News-mode archive entry provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Lists all posts published within the configured recent window (News mode).
 *
 * The window/ordering logic lives in {@see self::build_query_args()}, a pure
 * method unit-tested against fixture timestamps. Running the query and mapping
 * WordPress posts to entries is isolated in {@see self::run_query()} so the
 * selection logic needs no WordPress runtime to test.
 *
 * When the recent window contains no posts (a quiet news period, or content that
 * has aged past the window), {@see self::provide()} falls back to the latest
 * `news_fallback_count` published posts via {@see self::build_fallback_query_args()}
 * so the promoted surface is never empty when publishable content exists
 * (ticket 401).
 */
final class NewsEntryProvider implements ArchiveEntryProviderInterface {
	/**
	 * Hard upper bound on entries, so an unbounded window can't blow up the page.
	 */
	public const MAX_ENTRIES = 500;

	/**
	 * Provide the entries published within the recent window, falling back to the
	 * latest N posts when the window is empty.
	 *
	 * @param Settings $settings Current settings.
	 * @return ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array {
		$entries = $this->run_query( $this->build_query_args( $settings, time() ) );

		if ( array() !== $entries ) {
			return $entries;
		}

		return $this->run_query( $this->build_fallback_query_args( $settings ) );
	}

	/**
	 * Build the WP_Query args selecting published posts inside the window.
	 *
	 * Pure and deterministic given ($settings, $now): published posts only,
	 * newest first, bounded, and dated at-or-after `now - window_hours`.
	 *
	 * @param Settings $settings Current settings.
	 * @param int      $now      Current UNIX timestamp.
	 * @return array<string, mixed>
	 */
	public function build_query_args( Settings $settings, int $now ): array {
		$cutoff = $now - ( $settings->news_window_hours() * HOUR_IN_SECONDS );

		return array(
			'post_status'         => 'publish',
			'post_type'           => 'post',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => self::MAX_ENTRIES,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'date_query'          => array(
				array(
					'after'     => gmdate( 'Y-m-d H:i:s', $cutoff ),
					'inclusive' => true,
					'column'    => 'post_date_gmt',
				),
			),
		);
	}

	/**
	 * Build the WP_Query args for the empty-window fallback: the latest published
	 * posts, newest first, with no date constraint (ticket 401).
	 *
	 * Pure and deterministic given $settings. Identical to the windowed args minus
	 * the `date_query`, and bounded by `news_fallback_count` rather than
	 * {@see self::MAX_ENTRIES}, so the fallback can never select more than the
	 * administrator allows.
	 *
	 * @param Settings $settings Current settings.
	 * @return array<string, mixed>
	 */
	public function build_fallback_query_args( Settings $settings ): array {
		return array(
			'post_status'         => 'publish',
			'post_type'           => 'post',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => $settings->news_fallback_count(),
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);
	}

	/**
	 * Run the query and map each post to an archive entry.
	 *
	 * @param array<string, mixed> $args WP_Query args.
	 * @return ArchiveEntry[]
	 */
	private function run_query( array $args ): array {
		$query   = new \WP_Query( $args );
		$entries = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$entries[] = $this->map_post( $post );
			}
		}

		return $entries;
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
			wp_strip_all_tags( get_the_excerpt( $post ) ),
			(string) get_the_post_thumbnail_url( $post ),
			$this->term_names( $post->ID, 'category' ),
			$this->term_names( $post->ID, 'post_tag' ),
			get_the_author_meta( 'display_name', (int) $post->post_author ),
			is_string( $date ) ? $date : '',
			$this->is_noindex( $post->ID )
		);
	}

	/**
	 * Whether the post is marked noindex by a common SEO plugin meta key.
	 *
	 * Plugin-agnostic: reads the Yoast / Rank Math noindex markers without taking
	 * a dependency on either plugin. Unknown / absent meta means indexable.
	 *
	 * @param int $post_id The post ID.
	 * @return bool
	 */
	private function is_noindex( int $post_id ): bool {
		if ( '1' === get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			return true;
		}

		$rank_math = get_post_meta( $post_id, 'rank_math_robots', true );

		return is_array( $rank_math ) && in_array( 'noindex', $rank_math, true );
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
