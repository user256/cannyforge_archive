<?php
/**
 * Thrown by the wp_redirect()/wp_safe_redirect() test shims in place of a
 * redirect header + exit.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

use RuntimeException;

/**
 * Lets controller code paths that redirect (and then `exit`) be asserted on in
 * PHPUnit without terminating the test process. The target URL is carried on
 * the exception so tests can inspect the notice/query args it encodes.
 */
final class WpRedirectException extends RuntimeException {
	/**
	 * The redirect target URL.
	 *
	 * @var string
	 */
	public readonly string $location;

	/**
	 * Construct the exception.
	 *
	 * @param string $location The redirect target URL.
	 */
	public function __construct( string $location ) {
		parent::__construct( $location );
		$this->location = $location;
	}
}
