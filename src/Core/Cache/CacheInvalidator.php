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
 * transient.
 *
 * Term and user hooks matter because the cached HTML embeds the whole-database
 * filter-control option lists ({@see \CannyForge\Archive\Core\Archive\FilterOptionsProvider}) —
 * category/tag names and author display names — not just the promoted entries,
 * so a rename or deletion of any of those needs to invalidate the cache too.
 */
final class CacheInvalidator {
	/**
	 * The cache instance to invalidate.
	 *
	 * @var ArchiveCache
	 */
	private ArchiveCache $cache;

	/**
	 * Construct the invalidator.
	 *
	 * @param ArchiveCache $cache The archive cache.
	 */
	public function __construct( ArchiveCache $cache ) {
		$this->cache = $cache;
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
	 * Clear the archive cache.
	 *
	 * @return void
	 */
	public function invalidate(): void {
		$this->cache->clear();
	}
}
