<?php
/**
 * Minimal in-memory shim for the WordPress options API.
 *
 * Only what the engine's repository needs (get_option / update_option), backed
 * by a static store so tests can round-trip without a WordPress runtime. Guarded
 * so a real WordPress environment (or wp-stubs) takes precedence if present.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * In-memory get_option.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default_value Value returned when the option is unset.
	 * @return mixed
	 */
	function get_option( string $option, $default_value = false ) {
		$store = \CannyForge\Archive\Tests\OptionStore::all();
		return $store[ $option ] ?? $default_value;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * In-memory get_post_meta stub: no meta in the test runtime.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed
	 */
	function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
		unset( $post_id, $key );
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * In-memory update_option.
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Value to store.
	 * @param mixed  $autoload Autoload flag (ignored in the shim).
	 * @return bool
	 */
	function update_option( string $option, $value, $autoload = null ): bool {
		unset( $autoload );
		\CannyForge\Archive\Tests\OptionStore::set( $option, $value );
		return true;
	}
}
