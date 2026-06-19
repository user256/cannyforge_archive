<?php
/**
 * Records hook/menu registrations made through the WordPress hooks shim.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

/**
 * Static spy capturing the hooks registered during a test.
 */
final class HookSpy {
	/**
	 * Recorded registrations, keyed by hook name.
	 *
	 * @var array<string, list<callable>>
	 */
	private static array $hooks = array();

	/**
	 * Record a registration.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @return void
	 */
	public static function record( string $hook, callable $callback ): void {
		self::$hooks[ $hook ][] = $callback;
	}

	/**
	 * Whether a hook was registered.
	 *
	 * @param string $hook Hook name.
	 * @return bool
	 */
	public static function has( string $hook ): bool {
		return ! empty( self::$hooks[ $hook ] );
	}

	/**
	 * The first callback registered for a hook, or null when none.
	 *
	 * @param string $hook Hook name.
	 * @return callable|null
	 */
	public static function first( string $hook ): ?callable {
		return self::$hooks[ $hook ][0] ?? null;
	}

	/**
	 * All callbacks registered for a hook, or empty when none.
	 *
	 * @param string $hook Hook name.
	 * @return list<callable>
	 */
	public static function callbacks_for( string $hook ): array {
		return self::$hooks[ $hook ] ?? array();
	}

	/**
	 * Clear all recorded hooks (call between tests for isolation).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$hooks = array();
	}
}
