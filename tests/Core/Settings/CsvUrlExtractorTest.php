<?php
/**
 * Tests for the CSV URL extractor.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Settings;

use CannyForge\Archive\Core\Settings\CsvUrlExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Pulls the first URL from each CSV row, skipping headers and non-URL cells.
 */
class CsvUrlExtractorTest extends TestCase {
	/**
	 * Extract from the given CSV text.
	 *
	 * @param string $csv CSV contents.
	 * @return string[]
	 */
	private function extract( string $csv ): array {
		return ( new CsvUrlExtractor() )->extract( $csv );
	}

	/**
	 * A single-column URL list is extracted verbatim.
	 *
	 * @return void
	 */
	public function test_single_column(): void {
		$csv = "https://example.com/a\nhttps://example.com/b\n";

		$this->assertSame(
			array( 'https://example.com/a', 'https://example.com/b' ),
			$this->extract( $csv )
		);
	}

	/**
	 * The first URL-like cell per row is taken (url,title,score export).
	 *
	 * @return void
	 */
	public function test_first_url_per_row(): void {
		$csv = "https://example.com/a,Title A,42\nhttps://example.com/b,Title B,17\n";

		$this->assertSame(
			array( 'https://example.com/a', 'https://example.com/b' ),
			$this->extract( $csv )
		);
	}

	/**
	 * A header row whose first cell is not a URL is skipped naturally.
	 *
	 * @return void
	 */
	public function test_skips_header_row(): void {
		$csv = "url,title\nhttps://example.com/a,A\n";

		$this->assertSame( array( 'https://example.com/a' ), $this->extract( $csv ) );
	}

	/**
	 * A URL in a later column is found when the first cell isn't one.
	 *
	 * @return void
	 */
	public function test_url_in_later_column(): void {
		$csv = "1,https://example.com/a\n2,https://example.com/b\n";

		$this->assertSame(
			array( 'https://example.com/a', 'https://example.com/b' ),
			$this->extract( $csv )
		);
	}

	/**
	 * Quotes and CRLF line endings are handled; duplicates collapse.
	 *
	 * @return void
	 */
	public function test_quotes_crlf_and_dedupe(): void {
		$csv = "\"https://example.com/a\"\r\nhttps://example.com/a\r\nhttps://example.com/b\r\n";

		$this->assertSame(
			array( 'https://example.com/a', 'https://example.com/b' ),
			$this->extract( $csv )
		);
	}

	/**
	 * Non-HTTP values and empty input yield nothing.
	 *
	 * @return void
	 */
	public function test_ignores_non_http_and_empty(): void {
		$this->assertSame( array(), $this->extract( "ftp://x/y\nnot a url\n,,,\n" ) );
		$this->assertSame( array(), $this->extract( '' ) );
	}
}
