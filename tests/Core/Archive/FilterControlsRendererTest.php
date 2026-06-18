<?php
/**
 * Tests for the client-side filter controls renderer.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\Filters;
use CannyForge\Archive\Core\Archive\FilterControlsRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Only enabled filters render, with options derived from the entries.
 */
class FilterControlsRendererTest extends TestCase {
	/**
	 * Two representative entries spanning two categories/tags/authors/months.
	 *
	 * @return ArchiveEntry[]
	 */
	private function entries(): array {
		return array(
			new ArchiveEntry(
				'https://example.com/a',
				'A',
				'',
				'',
				array( 'News' ),
				array( 'world' ),
				'Jane Doe',
				'2026-06-18'
			),
			new ArchiveEntry(
				'https://example.com/b',
				'B',
				'',
				'',
				array( 'Sport' ),
				array( 'tennis' ),
				'John Roe',
				'2026-05-02'
			),
		);
	}

	/**
	 * Render the controls for the given filter toggle set.
	 *
	 * @param Filters $filters Toggle set.
	 * @return string
	 */
	private function render( Filters $filters ): string {
		return ( new FilterControlsRenderer() )->render( $this->entries(), $filters );
	}

	/**
	 * Default filters render search + category + tag + month, but not author.
	 *
	 * @return void
	 */
	public function test_renders_only_enabled_filters(): void {
		$html = $this->render( new Filters() );

		$this->assertStringContainsString( 'data-filter="search"', $html );
		$this->assertStringContainsString( 'data-filter="category"', $html );
		$this->assertStringContainsString( 'data-filter="tag"', $html );
		$this->assertStringContainsString( 'data-filter="month"', $html );
		$this->assertStringNotContainsString( 'data-filter="author"', $html );
	}

	/**
	 * Disabling every filter renders nothing.
	 *
	 * @return void
	 */
	public function test_empty_when_no_filter_enabled(): void {
		$html = $this->render( new Filters( false, false, false, false, false ) );

		$this->assertSame( '', $html );
	}

	/**
	 * Category options are the distinct category labels from the entries.
	 *
	 * @return void
	 */
	public function test_category_options_derived_from_entries(): void {
		$html = $this->render( new Filters( false, true, false, false, false ) );

		$this->assertStringContainsString( '<option value="News">News</option>', $html );
		$this->assertStringContainsString( '<option value="Sport">Sport</option>', $html );
	}

	/**
	 * The author filter, when enabled, lists the distinct authors.
	 *
	 * @return void
	 */
	public function test_author_options_when_enabled(): void {
		$html = $this->render( new Filters( false, false, false, false, true ) );

		$this->assertStringContainsString( 'data-filter="author"', $html );
		$this->assertStringContainsString( '<option value="Jane Doe">Jane Doe</option>', $html );
		$this->assertStringContainsString( '<option value="John Roe">John Roe</option>', $html );
	}

	/**
	 * Month options are distinct Y-m values, newest first.
	 *
	 * @return void
	 */
	public function test_month_options_newest_first(): void {
		$html = $this->render( new Filters( false, false, false, true, false ) );

		$june = strpos( $html, '2026-06' );
		$may  = strpos( $html, '2026-05' );

		$this->assertNotFalse( $june );
		$this->assertNotFalse( $may );
		$this->assertLessThan( $may, $june );
	}

	/**
	 * Control values are escaped.
	 *
	 * @return void
	 */
	public function test_escapes_option_values(): void {
		$entries = array(
			new ArchiveEntry( 'https://example.com/x', 'X', '', '', array( '<b>Hack</b>' ) ),
		);

		$html = ( new FilterControlsRenderer() )->render(
			$entries,
			new Filters( false, true, false, false, false )
		);

		$this->assertStringNotContainsString( '<b>Hack</b>', $html );
		$this->assertStringContainsString( '&lt;b&gt;', $html );
	}
}
