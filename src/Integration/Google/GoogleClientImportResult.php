<?php
/**
 * The outcome of importing a Google OAuth client JSON export.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Integration\Google;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable result of {@see GoogleClientConfigImporter::import()}.
 *
 * Carries either the extracted settings fields (on success) or an actionable
 * error message (on failure) — the caller uses this to render an honest
 * notice instead of silently dropping a failed import.
 */
final class GoogleClientImportResult {
	/**
	 * Whether the import succeeded.
	 *
	 * @var bool
	 */
	private bool $ok;

	/**
	 * Extracted settings fields, keyed by the plugin's posted field names.
	 *
	 * @var array<string, string>
	 */
	private array $fields;

	/**
	 * A human-readable, actionable error message. Empty on success.
	 *
	 * @var string
	 */
	private string $error;

	/**
	 * Construct the result.
	 *
	 * @param bool                  $ok     Whether the import succeeded.
	 * @param array<string, string> $fields Extracted fields.
	 * @param string                $error  Error message.
	 */
	private function __construct( bool $ok, array $fields, string $error ) {
		$this->ok     = $ok;
		$this->fields = $fields;
		$this->error  = $error;
	}

	/**
	 * Build a successful result.
	 *
	 * @param array<string, string> $fields Extracted fields.
	 * @return self
	 */
	public static function success( array $fields ): self {
		return new self( true, $fields, '' );
	}

	/**
	 * Build a failed result.
	 *
	 * @param string $error Actionable, human-readable error message.
	 * @return self
	 */
	public static function failure( string $error ): self {
		return new self( false, array(), $error );
	}

	/**
	 * Whether the import succeeded.
	 *
	 * @return bool
	 */
	public function ok(): bool {
		return $this->ok;
	}

	/**
	 * Extracted settings fields. Empty when {@see self::ok()} is false.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return $this->fields;
	}

	/**
	 * The actionable error message. Empty when {@see self::ok()} is true.
	 *
	 * @return string
	 */
	public function error(): string {
		return $this->error;
	}
}
