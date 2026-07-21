<?php
/**
 * Extracts URLs from CSV text.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pulls the URLs out of an uploaded CSV for the Blog-mode URL list.
 *
 * Pure and framework-free: takes raw CSV text and returns the first URL-like
 * value found in each row (so a "url,title,score" export and a one-column list
 * both work). A leading header row whose first cell isn't a URL is skipped
 * naturally because it yields no URL. De-duplicates while preserving order.
 */
final class CsvUrlExtractor {
	/**
	 * Extract URLs from raw CSV text.
	 *
	 * @param string $csv The CSV file contents.
	 * @return string[]
	 */
	public function extract( string $csv ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $csv );
		if ( false === $lines ) {
			return array();
		}

		$urls = array();
		foreach ( $lines as $line ) {
			$url = $this->first_url( $line );
			if ( '' !== $url ) {
				$urls[ $url ] = true;
			}
		}

		return array_keys( $urls );
	}

	/**
	 * The first HTTP(S) URL found among a row's comma-separated cells.
	 *
	 * @param string $line One CSV line.
	 * @return string
	 */
	private function first_url( string $line ): string {
		foreach ( explode( ',', $line ) as $cell ) {
			$value = trim( $cell, " \t\"'" );
			if ( $this->is_url( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Whether a value is an HTTP(S) URL.
	 *
	 * @param string $value Candidate value.
	 * @return bool
	 */
	private function is_url( string $value ): bool {
		if ( false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return 1 === preg_match( '#^https?://#i', $value );
	}
}
