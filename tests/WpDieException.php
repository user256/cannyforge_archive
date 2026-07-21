<?php
/**
 * Thrown by the wp_die() test shim in place of a fatal, non-returning exit.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

use RuntimeException;

/**
 * Lets controller code paths that call `wp_die()` be asserted on in PHPUnit
 * without terminating the test process the way a real WordPress fatal would.
 */
final class WpDieException extends RuntimeException {
}
