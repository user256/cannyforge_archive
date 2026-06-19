<?php
/**
 * Tests for the filter controls renderer.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\Filters;
use CannyForge\Archive\Core\Archive\FilterControlsRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Only enabled filters render, using whole-database option lists (ticket 301).
 */
class FilterControlsRendererTest extends TestCase {
	/**
	 * Representative whole-database option lists keyed by dimension.
	 *
	 * @return array<string, array<int, array{value: string, label: string}>>
	 */
	private function options(): array {
		return array(
			'category' => array(
				array(
					'value' => 'news',
					'label' => 'News',
				),
				array(
					'value' => 'sport',
					'label' => 'Sport',
				),
			),
			'tag'      => array(
				array(
					'value' => 'world',
					'label' => 'World',
				),
			),
			'author'   => array(
				array(
					'value' => 'jane-doe',
					'label' => 'Jane Doe',
				),
				array(
					'value' => 'john-roe',
					'label' => 'John Roe',
				),
			),
			'month'    => array(
				array(
					'value' => '2026-06',
					'label' => 'Jun 2026',
				),
				array(
					'value' => '2026-05',
					'label' => 'May 2026',
				),
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
		return ( new FilterControlsRenderer() )->render( $filters, $this->options() );
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
	 * Category options carry the whole-database slug value and human label.
	 *
	 * @return void
	 */
	public function test_category_options_use_slug_value_and_label(): void {
		$html = $this->render( new Filters( false, true, false, false, false ) );

		$this->assertStringContainsString( '<option value="news">News</option>', $html );
		$this->assertStringContainsString( '<option value="sport">Sport</option>', $html );
	}

	/**
	 * The author filter, when enabled, lists the whole-database authors.
	 *
	 * @return void
	 */
	public function test_author_options_when_enabled(): void {
		$html = $this->render( new Filters( false, false, false, false, true ) );

		$this->assertStringContainsString( 'data-filter="author"', $html );
		$this->assertStringContainsString( '<option value="jane-doe">Jane Doe</option>', $html );
		$this->assertStringContainsString( '<option value="john-roe">John Roe</option>', $html );
	}

	/**
	 * Month options preserve the order supplied (newest first).
	 *
	 * @return void
	 */
	public function test_month_options_preserve_order(): void {
		$html = $this->render( new Filters( false, false, false, true, false ) );

		$june = strpos( $html, '2026-06' );
		$may  = strpos( $html, '2026-05' );

		$this->assertNotFalse( $june );
		$this->assertNotFalse( $may );
		$this->assertLessThan( $may, $june );
	}

	/**
	 * Option values and labels are escaped.
	 *
	 * @return void
	 */
	public function test_escapes_option_values(): void {
		$html = ( new FilterControlsRenderer() )->render(
			new Filters( false, true, false, false, false ),
			array(
				'category' => array(
					array(
						'value' => 'hack',
						'label' => '<b>Hack</b>',
					),
				),
			)
		);

		$this->assertStringNotContainsString( '<b>Hack</b>', $html );
		$this->assertStringContainsString( '&lt;b&gt;', $html );
	}
}
