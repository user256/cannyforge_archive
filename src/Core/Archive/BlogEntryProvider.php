<?php
/**
 * Blog-mode archive entry provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Archive\PopularPostsSource;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Turns the administrator's curated URL list into archive entries (Blog mode).
 *
 * The list comes from manual text entry or CSV import. Selection — parse, trim,
 * validate, de-duplicate, cap — is a pure method ({@see self::select_urls()})
 * unit-tested without WordPress; turning each URL into a displayable entry
 * touches WordPress and is isolated in {@see self::resolve()}.
 *
 * When the curated list is empty (ticket 402), the provider falls back to a
 * best-effort "top content" set rather than render nothing, via a strict-
 * precedence tier chain: Google/Search Console cached IDs (ticket 405) →
 * most-commented (only if any post actually has comments) → Jetpack Stats views
 * (if that source is present) → newest. The precedence decision is the pure
 * {@see self::select_fallback_ids()}; the WordPress queries feeding it live in
 * {@see self::fallback_post_ids()}.
 *
 * Note (ticket 105 revisited): ticket 105 put *automatic popularity sourcing*
 * out of scope. Ticket 402 deliberately narrows that to allow a zero-dependency
 * core signal (comment_count) plus an optional in-process Jetpack Stats read —
 * but no external analytics integration. GA4/GSC sourcing remains separate
 * (ticket 403).
 */
final class BlogEntryProvider implements ArchiveEntryProviderInterface {
	/**
	 * Optional top-tier popularity source for the empty-list fallback (Search
	 * Console cache, or a no-op when none is wired).
	 *
	 * @var PopularPostsSource
	 */
	private PopularPostsSource $google;

	/**
	 * Optional popularity source for the empty-list fallback (Jetpack Stats, or a
	 * no-op when none is wired).
	 *
	 * @var PopularPostsSource
	 */
	private PopularPostsSource $popular;

	/**
	 * Construct the provider.
	 *
	 * @param PopularPostsSource|null $google  Top-tier popularity source for the
	 *                                         fallback (defaults to a no-op).
	 * @param PopularPostsSource|null $popular Secondary popularity source for the
	 *                                         fallback (defaults to a no-op).
	 */
	public function __construct( ?PopularPostsSource $google = null, ?PopularPostsSource $popular = null ) {
		$this->google  = $google ?? new NullPopularPostsSource();
		$this->popular = $popular ?? new NullPopularPostsSource();
	}

	/**
	 * Provide entries for the curated URL list, capped at the configured maximum.
	 *
	 * When the curated list is empty, falls back to a best-effort top-content set
	 * so the promoted surface is never blank (ticket 402).
	 *
	 * @param Settings $settings Current settings.
	 * @return ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array {
		$urls = $this->select_urls( $settings );

		if ( array() !== $urls ) {
			return $this->resolve( $urls );
		}

		return $this->resolve_ids( $this->fallback_post_ids( $settings ) );
	}

	/**
	 * Select the URLs to include: keep valid, trimmed, de-duplicated entries and
	 * cap at the configured maximum.
	 *
	 * Pure and deterministic given the settings, so the selection rules are
	 * testable without a WordPress runtime.
	 *
	 * @param Settings $settings Current settings.
	 * @return string[]
	 */
	public function select_urls( Settings $settings ): array {
		$valid = array();

		foreach ( $settings->blog_urls() as $url ) {
			$clean = trim( $url );

			if ( '' !== $clean && $this->is_valid_url( $clean ) ) {
				$valid[ $clean ] = true;
			}
		}

		return array_slice( array_keys( $valid ), 0, $settings->blog_max_urls() );
	}

	/**
	 * Choose the fallback post IDs from the available tier signals (ticket 402).
	 *
	 * Pure and deterministic: strict precedence, no WordPress.
	 *  1. Google/Search Console — used when cached IDs are available.
	 *  2. Most-commented — used only when `$has_comments` is true, so a site with
	 *     no comments does not present an arbitrary order as "popular".
	 *  3. Jetpack views — used when tiers 1–2 are empty and Jetpack returned IDs.
	 *  4. Newest — the final floor.
	 * The chosen list is de-duplicated and capped at $limit.
	 *
	 * @param int[] $google_ids    Post IDs ordered by Google/Search Console clicks, desc.
	 * @param int[] $commented_ids Post IDs ordered by comment count, desc.
	 * @param bool  $has_comments  Whether the top commented post has > 0 comments.
	 * @param int[] $jetpack_ids   Post IDs ordered by Jetpack views, desc.
	 * @param int[] $newest_ids    Post IDs ordered by date, desc.
	 * @param int   $limit         Maximum number of IDs to return.
	 * @return int[]
	 */
	public function select_fallback_ids(
		array $google_ids,
		array $commented_ids,
		bool $has_comments,
		array $jetpack_ids,
		array $newest_ids,
		int $limit
	): array {
		if ( array() !== $google_ids ) {
			$chosen = $google_ids;
		} elseif ( $has_comments && array() !== $commented_ids ) {
			$chosen = $commented_ids;
		} elseif ( array() !== $jetpack_ids ) {
			$chosen = $jetpack_ids;
		} else {
			$chosen = $newest_ids;
		}

		$unique = array_values( array_unique( array_filter( $chosen, static fn ( $id ) => $id > 0 ) ) );

		return array_slice( $unique, 0, max( 0, $limit ) );
	}

	/**
	 * Gather the tier signals from WordPress and pick the fallback post IDs.
	 *
	 * Isolates the WordPress queries (comment-ordered posts, Jetpack source,
	 * newest posts) and delegates the precedence decision to the pure
	 * {@see self::select_fallback_ids()}.
	 *
	 * @param Settings $settings Current settings.
	 * @return int[]
	 */
	private function fallback_post_ids( Settings $settings ): array {
		$limit  = $settings->blog_max_urls();
		$google = $this->google->is_available() ? $this->google->top_post_ids( $limit ) : array();

		$commented = $this->query_ids(
			array(
				'orderby' => 'comment_count',
				'order'   => 'DESC',
			),
			$limit
		);

		$jetpack = $this->popular->is_available() ? $this->popular->top_post_ids( $limit ) : array();

		$newest = $this->query_ids(
			array(
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$limit
		);

		return $this->select_fallback_ids(
			array_map( 'intval', $google ),
			$commented,
			$this->has_commented_post(),
			array_map( 'intval', $jetpack ),
			$newest,
			$limit
		);
	}

	/**
	 * Whether at least one published post has a comment.
	 *
	 * Gates tier 1: ordering by `comment_count` is only meaningful when some post
	 * actually has comments.
	 *
	 * @return bool
	 */
	private function has_commented_post(): bool {
		$ids = $this->query_ids(
			array(
				'orderby'       => 'comment_count',
				'order'         => 'DESC',
				'comment_count' => array(
					'value'   => 0,
					'compare' => '>',
				),
			),
			1
		);

		return array() !== $ids;
	}

	/**
	 * Run a published-posts query and return the post IDs only.
	 *
	 * @param array<string, mixed> $args  Query args merged over the base set.
	 * @param int                  $limit Maximum number of IDs.
	 * @return int[]
	 */
	private function query_ids( array $args, int $limit ): array {
		$query = new \WP_Query(
			array_merge(
				array(
					'post_status'         => 'publish',
					'post_type'           => 'post',
					'posts_per_page'      => $limit,
					'fields'              => 'ids',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
				),
				$args
			)
		);

		$ids = array();
		foreach ( $query->posts as $post ) {
			$ids[] = $post instanceof \WP_Post ? $post->ID : (int) $post;
		}

		return $ids;
	}

	/**
	 * Resolve a list of post IDs into enriched archive entries.
	 *
	 * Reuses the same enrichment as the curated path by deriving each post's
	 * permalink and mapping it through {@see self::map_post()}.
	 *
	 * @param int[] $ids Post IDs.
	 * @return ArchiveEntry[]
	 */
	private function resolve_ids( array $ids ): array {
		$entries = array();

		foreach ( $ids as $id ) {
			$url = get_permalink( $id );

			if ( is_string( $url ) && '' !== $url ) {
				$entries[] = $this->map_post( $url, (int) $id );
			}
		}

		return $entries;
	}

	/**
	 * Whether a string is an HTTP(S) URL we can resolve.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private function is_valid_url( string $url ): bool {
		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

		return 'http' === $scheme || 'https' === $scheme;
	}

	/**
	 * Resolve each URL to a displayable archive entry.
	 *
	 * A URL that maps to a local post is enriched (title, excerpt, image, terms,
	 * author, date) so the link-type toggles still apply; an unresolved URL falls
	 * back to a bare entry whose title defaults to the URL itself.
	 *
	 * @param string[] $urls Selected URLs.
	 * @return ArchiveEntry[]
	 */
	private function resolve( array $urls ): array {
		$entries = array();

		foreach ( $urls as $url ) {
			$post_id   = url_to_postid( $url );
			$entries[] = $post_id > 0 ? $this->map_post( $url, $post_id ) : new ArchiveEntry( $url );
		}

		return $entries;
	}

	/**
	 * Build an enriched entry from a resolved local post.
	 *
	 * @param string $url     The original URL.
	 * @param int    $post_id The resolved post ID.
	 * @return ArchiveEntry
	 */
	private function map_post( string $url, int $post_id ): ArchiveEntry {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return new ArchiveEntry( $url );
		}

		$date = get_the_date( 'Y-m-d', $post );

		return new ArchiveEntry(
			$url,
			get_the_title( $post ),
			trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) get_the_excerpt( $post ) ) ) ) ?? '' ),
			(string) get_the_post_thumbnail_url( $post ),
			$this->term_names( $post_id, 'category' ),
			$this->term_names( $post_id, 'post_tag' ),
			get_the_author_meta( 'display_name', (int) $post->post_author ),
			is_string( $date ) ? $date : '',
			$this->is_noindex( $post_id ),
			$post_id
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
