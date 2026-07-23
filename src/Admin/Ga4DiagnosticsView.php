<?php
/**
 * Renders GA4 page-path diagnostics for the settings screen.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\Ga4CacheStore;

/**
 * Presentation-only view for checking GA4 paths against local WordPress posts.
 */
final class Ga4DiagnosticsView {
	/**
	 * GA4 cache store.
	 *
	 * @var Ga4CacheStore
	 */
	private Ga4CacheStore $cache;

	/**
	 * WordPress post-title resolver.
	 *
	 * @var callable
	 */
	private $post_title;

	/**
	 * WordPress permalink resolver.
	 *
	 * @var callable
	 */
	private $permalink;

	/**
	 * Construct the diagnostics view.
	 *
	 * @param Ga4CacheStore $cache      GA4 cache store.
	 * @param callable      $post_title Post-title resolver.
	 * @param callable      $permalink   Permalink resolver.
	 */
	public function __construct( Ga4CacheStore $cache, callable $post_title, callable $permalink ) {
		$this->cache      = $cache;
		$this->post_title = $post_title;
		$this->permalink  = $permalink;
	}

	/**
	 * Render raw GA4 page paths and any paths resolved to local posts.
	 *
	 * @return void
	 */
	public function render(): void {
		$post_ids    = $this->cache->get_post_ids();
		$source_urls = $this->cache->get_source_urls();
		$rows        = $this->local_rows( $post_ids );

		echo '<section class="cf-search-console-curator cf-ga4-diagnostics" aria-labelledby="cf-ga4-diagnostics-title">';
		echo '<h3 id="cf-ga4-diagnostics-title">' . esc_html__( 'GA4 top pages', 'cannyforge-archive' ) . '</h3>';
		echo '<p class="description">';
		echo esc_html__( 'Review the page paths returned by GA4 and confirm whether they resolve to published posts on this WordPress install.', 'cannyforge-archive' );
		echo '</p>';

		if ( empty( $source_urls ) ) {
			$this->render_no_source_paths();
		} else {
			$this->render_source_paths( $source_urls, count( $rows ) );
		}

		if ( ! empty( $rows ) ) {
			echo '<h4>' . esc_html__( 'Matched local content', 'cannyforge-archive' ) . '</h4>';
			$this->render_local_rows( $rows );
		} elseif ( ! empty( $source_urls ) ) {
			echo '<p class="cf-search-console-curator__empty cf-ga4-diagnostics__empty">';
			echo esc_html__( 'None of these paths matched a published post on this WordPress install. This is expected when testing production data on local or staging content.', 'cannyforge-archive' );
			echo '</p>';
		}

		echo '</section>';
	}

	/**
	 * Resolve cached GA4 post IDs to display rows.
	 *
	 * @param int[] $post_ids Cached local post IDs.
	 * @return array<int, array{title: string, url: string}>
	 */
	private function local_rows( array $post_ids ): array {
		$rows = array();

		foreach ( $post_ids as $post_id ) {
			$title = trim( (string) ( $this->post_title )( $post_id ) );
			$url   = (string) ( $this->permalink )( $post_id );

			if ( '' === $url ) {
				continue;
			}

			$rows[] = array(
				'title' => '' !== $title ? $title : __( '(Untitled post)', 'cannyforge-archive' ),
				'url'   => $url,
			);
		}

		return $rows;
	}

	/**
	 * Render locally resolved GA4 posts.
	 *
	 * @param array<int, array{title: string, url: string}> $rows Display rows.
	 * @return void
	 */
	private function render_local_rows( array $rows ): void {
		echo '<ol class="cf-search-console-curator__list cf-ga4-diagnostics__list">';
		foreach ( $rows as $row ) {
			printf(
				'<li class="cf-search-console-curator__item cf-ga4-diagnostics__item"><span class="cf-search-console-curator__title cf-ga4-diagnostics__title">%1$s</span> <a class="cf-search-console-curator__link cf-ga4-diagnostics__link" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></li>',
				esc_html( $row['title'] ),
				esc_url( $row['url'] ),
				esc_html__( 'View', 'cannyforge-archive' )
			);
		}
		echo '</ol>';
	}

	/**
	 * Explain that no GA4 source paths were retained for the latest refresh.
	 *
	 * @return void
	 */
	private function render_no_source_paths(): void {
		echo '<p class="cf-search-console-curator__empty cf-ga4-diagnostics__empty">';
		echo esc_html__( 'No cached GA4 pages are available yet. Refresh GA4 in the Google setup wizard to load them here.', 'cannyforge-archive' );
		echo '</p>';
	}

	/**
	 * Render every cached GA4 page path, even when some paths resolve locally.
	 *
	 * @param string[] $source_urls Raw GA4 page paths.
	 * @param int      $matched     Number of paths resolved to local posts.
	 * @return void
	 */
	private function render_source_paths( array $source_urls, int $matched ): void {
		echo '<h4>' . esc_html__( 'Raw GA4 page paths', 'cannyforge-archive' ) . '</h4>';
		echo '<p class="description">';
		printf(
			/* translators: 1: number of page paths returned by GA4. 2: number that matched local published posts. */
			esc_html__( 'GA4 returned %1$d page paths; %2$d matched published posts on this WordPress install.', 'cannyforge-archive' ),
			absint( count( $source_urls ) ),
			absint( $matched )
		);
		echo '</p>';
		echo '<ol class="cf-search-console-curator__list cf-ga4-diagnostics__list">';
		foreach ( $source_urls as $url ) {
			printf(
				'<li class="cf-search-console-curator__item cf-ga4-diagnostics__item"><code>%1$s</code> <a class="cf-search-console-curator__link cf-ga4-diagnostics__link" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></li>',
				esc_html( $url ),
				esc_url( $url ),
				esc_html__( 'Open path', 'cannyforge-archive' )
			);
		}
		echo '</ol>';
	}
}
