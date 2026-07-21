<?php
/**
 * Records JSON responses sent through the wp_send_json_* shims.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

/**
 * Static spy capturing the payload of the most recent `wp_send_json_success()`
 * / `wp_send_json_error()` call, so an AJAX handler's response can be asserted
 * on directly instead of parsing captured output.
 */
final class AjaxResponseSpy {
	/**
	 * The data passed to the most recent `wp_send_json_success()` call, or a
	 * sentinel when none has happened.
	 *
	 * @var mixed
	 */
	private static mixed $success = self::NONE;

	/**
	 * The data passed to the most recent `wp_send_json_error()` call, or a
	 * sentinel when none has happened.
	 *
	 * @var mixed
	 */
	private static mixed $error = self::NONE;

	/**
	 * Sentinel distinguishing "never called" from a call carrying null data.
	 */
	private const NONE = '__cannyforge_test_ajax_response_none__';

	/**
	 * Record a success response.
	 *
	 * @param mixed $data The response data.
	 * @return void
	 */
	public static function record_success( mixed $data ): void {
		self::$success = $data;
	}

	/**
	 * Record an error response.
	 *
	 * @param mixed $data The response data.
	 * @return void
	 */
	public static function record_error( mixed $data ): void {
		self::$error = $data;
	}

	/**
	 * Whether a success response was recorded.
	 *
	 * @return bool
	 */
	public static function has_success(): bool {
		return self::NONE !== self::$success;
	}

	/**
	 * Whether an error response was recorded.
	 *
	 * @return bool
	 */
	public static function has_error(): bool {
		return self::NONE !== self::$error;
	}

	/**
	 * The data passed to the most recent success response.
	 *
	 * @return mixed
	 */
	public static function success(): mixed {
		return self::$success;
	}

	/**
	 * The data passed to the most recent error response.
	 *
	 * @return mixed
	 */
	public static function error(): mixed {
		return self::$error;
	}

	/**
	 * Clear recorded responses (call between tests for isolation).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$success = self::NONE;
		self::$error   = self::NONE;
	}
}
