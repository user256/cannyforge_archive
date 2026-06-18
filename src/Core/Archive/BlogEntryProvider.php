<?php
/**
 * Blog-mode archive entry provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Archive\ArchiveEntryProviderInterface;
use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Turns the administrator's curated URL list into archive entries (Blog mode).
 *
 * The list comes from manual text entry or CSV import; sourcing it from
 * analytics (Snowflake / Adobe / popularity scoring) is explicitly out of scope
 * (see ticket 105). Selection — parse, trim, validate, de-duplicate, cap — is a
 * pure method ({@see self::select_urls()}) unit-tested without WordPress; turning
 * each URL into a displayable entry touches WordPress and is isolated in
 * {@see self::resolve()}.
 */
final class BlogEntryProvider implements ArchiveEntryProviderInterface {
	/**
	 * Provide entries for the curated URL list, capped at the configured maximum.
	 *
	 * @param Settings $settings Current settings.
	 * @return ArchiveEntry[]
	 */
	public function provide( Settings $settings ): array {
		return $this->resolve( $this->select_urls( $settings ) );
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
			wp_strip_all_tags( get_the_excerpt( $post ) ),
			(string) get_the_post_thumbnail_url( $post ),
			$this->term_names( $post_id, 'category' ),
			$this->term_names( $post_id, 'post_tag' ),
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
