<?php
/**
 * AJAX endpoint serving whole-database archive search/filter results.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Contracts\SettingsRepositoryInterface;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\ContentIndexProvider;

/**
 * Serves paginated, whole-database search/filter results for the archive
 * (ticket 301).
 *
 * The archive page's default view promotes a bounded set; this endpoint is what
 * makes *all* content findable. It runs a paginated `WP_Query` over the entire
 * database via {@see ContentIndexProvider} and returns rendered entry HTML plus
 * pagination metadata as JSON. A public (`nopriv`) read endpoint: it exposes only
 * already-public published posts, verifies a nonce to bind requests to the
 * archive page, and sanitises every input. Thin controller — the query and
 * rendering live in Core.
 */
final class ArchiveSearchEndpoint {
	/**
	 * The admin-ajax action name.
	 */
	public const ACTION = 'cannyforge_archive_search';

	/**
	 * The nonce action/name used to bind requests to the archive page.
	 */
	public const NONCE = 'cannyforge_archive_search';

	/**
	 * Settings persistence (link-type toggles, default page size).
	 *
	 * @var SettingsRepositoryInterface
	 */
	private SettingsRepositoryInterface $repository;

	/**
	 * Whole-database content query.
	 *
	 * @var ContentIndexProvider
	 */
	private ContentIndexProvider $index;

	/**
	 * Entry renderer (shared with the page so results render identically).
	 *
	 * @var ArchiveRenderer
	 */
	private ArchiveRenderer $renderer;

	/**
	 * Construct the endpoint.
	 *
	 * @param SettingsRepositoryInterface $repository Settings persistence.
	 * @param ContentIndexProvider        $index      Whole-database content query.
	 * @param ArchiveRenderer             $renderer   Entry renderer.
	 */
	public function __construct(
		SettingsRepositoryInterface $repository,
		ContentIndexProvider $index,
		ArchiveRenderer $renderer
	) {
		$this->repository = $repository;
		$this->index      = $index;
		$this->renderer   = $renderer;
	}

	/**
	 * Register the AJAX handlers for logged-in and anonymous visitors.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Handle a search request: verify, build the query, run it, return JSON.
	 *
	 * @return void
	 */
	public function handle(): void {
		check_ajax_referer( self::NONCE, 'nonce' );

		$query    = $this->query_from_request();
		$settings = $this->repository->get();
		$page     = $this->index->provide( $query );

		wp_send_json_success(
			array(
				'html'        => $this->renderer->render_entries( $page->entries(), $settings ),
				'total'       => $page->total(),
				'page'        => $page->page(),
				'per_page'    => $page->per_page(),
				'total_pages' => $page->total_pages(),
				'has_next'    => $page->has_next(),
				'has_prev'    => $page->has_prev(),
				'is_active'   => $query->is_active(),
			)
		);
	}

	/**
	 * Build a sanitised content query from the request superglobals.
	 *
	 * The nonce has already been verified by {@see self::handle()}; each field is
	 * still individually sanitised before use.
	 *
	 * @return ContentQuery
	 */
	private function query_from_request(): ContentQuery {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce verified in handle() via check_ajax_referer().
		return ContentQuery::from_array(
			array(
				'search'   => isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['search'] ) ) : '',
				'category' => isset( $_REQUEST['category'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['category'] ) ) : '',
				'tag'      => isset( $_REQUEST['tag'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['tag'] ) ) : '',
				'author'   => isset( $_REQUEST['author'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['author'] ) ) : '',
				'month'    => isset( $_REQUEST['month'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['month'] ) ) : '',
				'page'     => isset( $_REQUEST['page'] ) ? absint( wp_unslash( $_REQUEST['page'] ) ) : 1,
				'per_page' => isset( $_REQUEST['per_page'] ) ? absint( wp_unslash( $_REQUEST['per_page'] ) ) : 20,
			)
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}
