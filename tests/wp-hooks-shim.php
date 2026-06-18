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
