<?php
/**
 * Namespace-scoped override of wp_safe_redirect() for CannyForge\Archive\Admin.
 *
 * The global wp_safe_redirect() shim in wp-hooks-shim.php returns bool (the
 * real WordPress contract) because Frontend\ArchivePage relies on that return
 * value to decide whether to try a fallback URL before giving up. The Admin
 * controllers (GoogleConnectionController, Ga4RefreshController,
 * SearchConsoleRefreshController) don't check the return value — they always
 * call `exit;` right after, exactly like production WordPress admin-post
 * handlers do. A bare `exit;` is a language construct that can never be
 * intercepted in PHP, so testing that path needs wp_safe_redirect() itself to
 * throw before control ever reaches it.
 *
 * PHP resolves an unqualified function call by first checking for a
 * same-named function in the *calling* namespace, then falling back to the
 * global one. Defining a throwing wp_safe_redirect() here, inside
 * CannyForge\Archive\Admin, means only code in that namespace gets the
 * throwing behaviour; Frontend\ArchivePage and everything else still resolve
 * to the global, bool-returning shim. Without this split, whichever shim
 * loaded first silently won for every namespace (via function_exists()),
 * which let the Admin controllers' bare `exit;` kill the PHPUnit process
 * outright — with a 0 exit code, masking the truncation as a green run.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

use CannyForge\Archive\Tests\WpRedirectException;

if ( ! function_exists( __NAMESPACE__ . '\\wp_safe_redirect' ) ) {
	/**
	 * In-memory wp_safe_redirect for the Admin namespace: throws instead of
	 * sending headers + exiting.
	 *
	 * @param string $location Redirect target.
	 * @param int    $status   HTTP status (ignored).
	 * @return never
	 * @throws WpRedirectException Always, in place of a real redirect + exit.
	 */
	function wp_safe_redirect( string $location, int $status = 302 ): never {
		unset( $status );
		throw new WpRedirectException( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- test-only control-flow signal, never rendered as output.
	}
}
