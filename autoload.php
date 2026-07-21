<?php
/**
 * Runtime autoloader for the shipped plugin.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( string $fqcn ): void {
		$prefix = 'CannyForge\\Archive\\';

		if ( 0 !== strpos( $fqcn, $prefix ) ) {
			return;
		}

		$relative = substr( $fqcn, strlen( $prefix ) );
		$path     = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $path ) ) {
			require $path;
		}
	}
);
