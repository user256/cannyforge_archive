<?php
/**
 * Minimal shim for the WordPress Transients API.
 *
 * Backed by a static in-memory store so cache hit/miss/invalidation can be
 * unit-tested without a WordPress runtime. Guarded so a real WordPress
 * environment (or wp-stubs) takes precedence.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * In-memory get_transient.
	 *
	 * @param string $transient Transient name.
	 * @return mixed
	 */
	function get_transient( string $transient ) {
		$store = \CannyForge\Archive\Tests\TransientStore::all();
		return $store[ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * In-memory set_transient.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Value to store.
	 * @param int    $expiration Time until expiration in seconds (ignored in shim).
	 * @return bool
	 */
	function set_transient( string $transient, $value, int $expiration = 0 ): bool {
		unset( $expiration );
		\CannyForge\Archive\Tests\TransientStore::set( $transient, $value );
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * In-memory delete_transient.
	 *
	 * @param string $transient Transient name.
	 * @return bool
	 */
	function delete_transient( string $transient ): bool {
		\CannyForge\Archive\Tests\TransientStore::delete( $transient );
		return true;
	}
}
