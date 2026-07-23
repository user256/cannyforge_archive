<?php
/**
 * Shells out to the disposable wp-env `cli` container's WP-CLI.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration\Support;

/**
 * Thin wrapper around `npx @wordpress/env run cli wp ...`, used by the real-WordPress
 * integration suite (ticket 603) to drive setup/teardown and to read state
 * (options, plugin activation, raw DB counts) that only exists inside the
 * running WordPress instance.
 *
 * Wp-env's own decorative "Starting ..." / "Ran ..." lines go to stderr, so
 * plain stdout capture is already clean — see the manual verification in the
 * ticket's decisions log.
 */
final class WpEnvCli {
	/**
	 * Run a `wp` sub-command inside the wp-env `cli` container and return its
	 * trimmed stdout.
	 *
	 * @param string ...$args The wp-cli arguments (e.g. 'option', 'get', 'siteurl').
	 * @return string
	 */
	public static function run( string ...$args ): string {
		$command = sprintf(
			'cd %s && npx @wordpress/env run cli wp %s 2>/dev/null',
			escapeshellarg( self::repo_root() ),
			implode( ' ', array_map( 'escapeshellarg', $args ) )
		);

		$output = shell_exec( $command ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec -- drives the disposable wp-env container from a black-box PHPUnit test; no WordPress runtime is loaded to use WP APIs instead.

		return is_string( $output ) ? trim( $output ) : '';
	}

	/**
	 * Run a single-scalar `wp db query` and return its first line, trimmed.
	 *
	 * @param string $sql The SQL to run.
	 * @return string
	 */
	public static function scalar_query( string $sql ): string {
		$output = self::run( 'db', 'query', $sql, '--skip-column-names' );
		$split  = preg_split( '/\r\n|\r|\n/', $output );
		$lines  = false === $split ? array() : $split;

		return trim( (string) ( $lines[0] ?? '' ) );
	}

	/**
	 * The wp-env development site's base URL.
	 *
	 * Overridable via `WP_ENV_BASE_URL` so CI can point the suite at a
	 * differently-configured port without editing this file.
	 *
	 * @return string
	 */
	public static function base_url(): string {
		$env = getenv( 'WP_ENV_BASE_URL' );

		return is_string( $env ) && '' !== $env ? rtrim( $env, '/' ) : 'http://localhost:8891';
	}

	/**
	 * The repository root containing `.wp-env.json`.
	 *
	 * @return string
	 */
	private static function repo_root(): string {
		return dirname( __DIR__, 3 );
	}
}
