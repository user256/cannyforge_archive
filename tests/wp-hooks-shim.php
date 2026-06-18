<?php
/**
 * Minimal shim for the WordPress hook / admin-menu functions the composition
 * root and admin page touch, so wiring can be smoke-tested without a WordPress
 * runtime. Hooks are recorded in {@see \CannyForge\Archive\Tests\HookSpy};
 * each function is guarded so a real WordPress environment takes precedence.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Record an action registration.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @return bool
	 */
	function add_action( string $hook, callable $callback ): bool {
		\CannyForge\Archive\Tests\HookSpy::record( $hook, $callback );
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Record a filter registration.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @return bool
	 */
	function add_filter( string $hook, callable $callback ): bool {
		\CannyForge\Archive\Tests\HookSpy::record( 'filter:' . $hook, $callback );
		return true;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	/**
	 * Record a shortcode registration.
	 *
	 * @param string   $tag      Shortcode tag.
	 * @param callable $callback Callback.
	 * @return void
	 */
	function add_shortcode( string $tag, callable $callback ): void {
		\CannyForge\Archive\Tests\HookSpy::record( 'shortcode:' . $tag, $callback );
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	/**
	 * Read a query var, returning the supplied fallback in the test runtime.
	 *
	 * @param string $query_var Query var name.
	 * @param mixed  $fallback  Value returned when unset.
	 * @return mixed
	 */
	function get_query_var( string $query_var, $fallback = '' ) {
		unset( $query_var );
		return $fallback;
	}
}

if ( ! function_exists( 'get_pagenum_link' ) ) {
	/**
	 * Build a paginated URL for the given page.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	function get_pagenum_link( int $page ): string {
		return 'http://example.test/page/' . $page . '/';
	}
}

if ( ! function_exists( 'home_url' ) ) {
	/**
	 * Build a home URL.
	 *
	 * @param string $path Path under the site root.
	 * @return string
	 */
	function home_url( string $path = '' ): string {
		return 'http://example.test' . $path;
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	/**
	 * Record a menu-page registration and return the hook suffix.
	 *
	 * @param string   $page_title Page title.
	 * @param string   $menu_title Menu title.
	 * @param string   $capability Capability.
	 * @param string   $menu_slug  Slug.
	 * @param callable $callback   Render callback.
	 * @param string   $icon       Dashicon.
	 * @return string
	 */
	function add_menu_page(
		string $page_title,
		string $menu_title,
		string $capability,
		string $menu_slug,
		callable $callback,
		string $icon = ''
	): string {
		unset( $page_title, $menu_title, $capability, $icon );
		\CannyForge\Archive\Tests\HookSpy::record( 'menu:' . $menu_slug, $callback );
		return 'toplevel_page_' . $menu_slug;
	}
}

if ( ! defined( 'EP_ROOT' ) ) {
	define( 'EP_ROOT', 64 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! function_exists( 'add_rewrite_endpoint' ) ) {
	/**
	 * Record a rewrite-endpoint registration.
	 *
	 * @param string $name      Endpoint slug.
	 * @param int    $places    Endpoint mask.
	 * @param string $query_var Query var.
	 * @return void
	 */
	function add_rewrite_endpoint( string $name, int $places, string $query_var = '' ): void {
		unset( $places );
		\CannyForge\Archive\Tests\HookSpy::record( 'endpoint:' . $name, static fn () => $query_var );
	}
}

if ( ! function_exists( 'status_header' ) ) {
	/**
	 * No-op status header.
	 *
	 * @param int $code HTTP status code.
	 * @return void
	 */
	function status_header( int $code ): void {
		unset( $code );
	}
}

if ( ! function_exists( 'is_category' ) ) {
	/**
	 * Conditional tag stub: not a category archive in the test runtime.
	 *
	 * @return bool
	 */
	function is_category(): bool {
		return false;
	}
}

if ( ! function_exists( 'is_tag' ) ) {
	/**
	 * Conditional tag stub: not a tag archive in the test runtime.
	 *
	 * @return bool
	 */
	function is_tag(): bool {
		return false;
	}
}

if ( ! function_exists( 'is_author' ) ) {
	/**
	 * Conditional tag stub: not an author archive in the test runtime.
	 *
	 * @return bool
	 */
	function is_author(): bool {
		return false;
	}
}

if ( ! function_exists( 'is_date' ) ) {
	/**
	 * Conditional tag stub: not a date archive in the test runtime.
	 *
	 * @return bool
	 */
	function is_date(): bool {
		return false;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Build an admin URL.
	 *
	 * @param string $path Path under wp-admin.
	 * @return string
	 */
	function admin_url( string $path = '' ): string {
		return 'http://example.test/wp-admin/' . $path;
	}
}
