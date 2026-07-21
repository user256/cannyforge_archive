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
 * Hooks into WordPress post lifecycle and plugin settings save events to
 * clear the rendered archive HTML transient.
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
