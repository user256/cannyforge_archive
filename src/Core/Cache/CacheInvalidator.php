<?php
/**
 * Invalidates the archive HTML cache when content or settings change.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks into WordPress post lifecycle, taxonomy term lifecycle, user profile
 * changes, and plugin settings save events to clear the rendered archive HTML
 * transient plus the whole-database search response cache (ticket 608).
 *
 * Term and user hooks matter because both caches embed whole-database data
 * beyond just the promoted/matched entries — the HTML cache embeds the
 * filter-control option lists ({@see \CannyForge\Archive\Core\Archive\FilterOptionsProvider}),
 * and each cached search response embeds rendered entry HTML carrying
 * category/tag names and author display names — so a rename or deletion of
 * any of those needs to invalidate both caches, not just a post save/delete.
 */
final class CacheInvalidator {
	/**
	 * The HTML fragment cache to invalidate.
	 *
	 * @var ArchiveCache
	 */
	private ArchiveCache $cache;

	/**
	 * The search response cache to invalidate.
	 *
	 * @var SearchResultCache
	 */
	private SearchResultCache $search_cache;

	/**
	 * Construct the invalidator.
	 *
	 * @param ArchiveCache           $cache        The archive HTML cache.
	 * @param SearchResultCache|null $search_cache The search response cache.
	 */
	public function __construct( ArchiveCache $cache, ?SearchResultCache $search_cache = null ) {
		$this->cache        = $cache;
		$this->search_cache = $search_cache ?? new SearchResultCache();
	}

	/**
	 * Register the invalidation hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'save_post', array( $this, 'invalidate' ) );
		add_action( 'deleted_post', array( $this, 'invalidate' ) );
		add_action( 'cannyforge_archive_settings_saved', array( $this, 'invalidate' ) );

		// Taxonomy terms: generic (taxonomy-agnostic) hooks fire for every
		// taxonomy, including the category/tag dropdowns embedded in the cache.
		add_action( 'created_term', array( $this, 'invalidate' ) );
		add_action( 'edited_term', array( $this, 'invalidate' ) );
		add_action( 'delete_term', array( $this, 'invalidate' ) );

		// Authors: the cached author dropdown shows display names, so a profile
		// edit or account deletion can leave stale entries.
		add_action( 'profile_update', array( $this, 'invalidate' ) );
		add_action( 'deleted_user', array( $this, 'invalidate' ) );
	}

	/**
	 * Clear the archive HTML cache and the search response cache.
	 *
	 * @return void
	 */
	public function invalidate(): void {
		$this->cache->clear();
		$this->search_cache->clear();
	}
}
