<?php
/**
 * Reads an uploaded Google OAuth client JSON file into settings fields.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleClientConfigImporter;

/**
 * Shared reader for the `google_client_json` file upload.
 *
 * Both the settings-form save and the Google setup wizard accept the OAuth
 * client JSON exported from Google Cloud; this class owns the upload
 * plumbing and import-error capture so the two save paths cannot drift.
 */
final class GoogleClientJsonUploadReader {
	/**
	 * The file input name carrying the OAuth client JSON.
	 */
	public const FILE_FIELD = 'google_client_json';

	/**
	 * Google OAuth client JSON importer.
	 *
	 * @var GoogleClientConfigImporter
	 */
	private GoogleClientConfigImporter $importer;

	/**
	 * The actionable error from the last import attempt, or '' when none was
	 * attempted or it succeeded.
	 *
	 * @var string
	 */
	private string $error = '';

	/**
	 * Construct the reader.
	 *
	 * @param GoogleClientConfigImporter|null $importer Client JSON importer.
	 */
	public function __construct( ?GoogleClientConfigImporter $importer = null ) {
		$this->importer = $importer ?? new GoogleClientConfigImporter();
	}

	/**
	 * Read the uploaded client JSON and extract its credential fields.
	 *
	 * Returns an empty array when no file was uploaded or the upload is not a
	 * genuine temp file. When a file was uploaded but rejected (malformed,
	 * oversized, or not a Web client export), records an actionable error in
	 * {@see self::error()} so the failure is reported instead of silent.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		$this->error = '';

		$contents = $this->uploaded_file_contents();
		if ( '' === $contents ) {
			return array();
		}

		$result = $this->importer->import( $contents );
		if ( ! $result->ok() ) {
			$this->error = $result->error();
			return array();
		}

		return $result->fields();
	}

	/**
	 * The actionable error from the last {@see self::fields()} call.
	 *
	 * @return string
	 */
	public function error(): string {
		return $this->error;
	}

	/**
	 * Read the small uploaded temp file into memory.
	 *
	 * @return string
	 */
	private function uploaded_file_contents(): string {
		// Nonce + capability are verified by the save handlers before this runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES[ self::FILE_FIELD ]['tmp_name'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- path validated via is_uploaded_file below.
		$tmp = (string) $_FILES[ self::FILE_FIELD ]['tmp_name'];
		if ( ! is_uploaded_file( $tmp ) ) {
			return '';
		}

		$contents = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a small uploaded temp file.

		return is_string( $contents ) ? $contents : '';
	}
}
