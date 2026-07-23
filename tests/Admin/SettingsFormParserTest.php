<?php
/**
 * Tests for the settings form parser.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\SettingsFormParser;
use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\PaginationStyle;
use PHPUnit\Framework\TestCase;

/**
 * The parser maps a raw form payload to a Settings value object.
 */
class SettingsFormParserTest extends TestCase {
	/**
	 * A full payload maps every field across.
	 *
	 * @return void
	 */
	public function test_parses_a_full_payload(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'mode'                => 'news',
				'pagination_limit'    => '4',
				'pagination_style'    => 'leading_tail',
				'news_window_hours'   => '36',
				'news_fallback_count' => '20',
				'blog_max_urls'       => '50',
				'link_title'          => '1',
				'link_description'    => '1',
				'link_categories'     => '1',
				'link_author'         => '1',
				'link_published_date' => '1',
				'filter_search'       => '1',
				'filter_author'       => '1',
				'blog_urls'           => "https://example.com/a\nhttps://example.com/b",
			)
		);

		$this->assertSame( Mode::News, $settings->mode() );
		$this->assertSame( 4, $settings->pagination_limit() );
		$this->assertSame( PaginationStyle::LeadingWithTail, $settings->pagination_style() );
		$this->assertSame( 36, $settings->news_window_hours() );
		$this->assertSame( 20, $settings->news_fallback_count() );
		$this->assertTrue( $settings->link_types()->title() );
		$this->assertTrue( $settings->link_types()->description() );
		$this->assertFalse( $settings->link_types()->featured_image() );
		$this->assertTrue( $settings->link_types()->categories() );
		$this->assertFalse( $settings->link_types()->tags() );
		$this->assertTrue( $settings->link_types()->author() );
		$this->assertTrue( $settings->link_types()->published_date() );
		$this->assertTrue( $settings->filters()->search() );
		$this->assertTrue( $settings->filters()->author() );
		$this->assertSame( 'cards', $settings->theme()->layout() );
	}

	/**
	 * Absent checkboxes parse as false (unchecked boxes are not posted).
	 *
	 * @return void
	 */
	public function test_absent_checkboxes_are_false(): void {
		$settings = ( new SettingsFormParser() )->parse( array( 'mode' => 'blog' ) );

		$this->assertFalse( $settings->link_types()->title() );
		$this->assertFalse( $settings->link_types()->description() );
		$this->assertFalse( $settings->link_types()->categories() );
		$this->assertFalse( $settings->filters()->search() );
		$this->assertFalse( $settings->filters()->author() );
	}

	/**
	 * Multi-select category/tag fields map to string lists.
	 *
	 * @return void
	 */
	public function test_multiselect_content_selection_fields_are_parsed(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'select_include_categories' => array( 'News', 'Guides' ),
				'select_exclude_tags'       => array( 'Sponsored', 'Internal' ),
			)
		);

		$this->assertSame( array( 'News', 'Guides' ), $settings->content_selection()->include_categories() );
		$this->assertSame( array( 'Sponsored', 'Internal' ), $settings->content_selection()->exclude_tags() );
	}

	/**
	 * The blog-URL textarea splits on newlines and commas, trimming blanks.
	 *
	 * @return void
	 */
	public function test_blog_urls_textarea_splits_and_trims(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'mode'      => 'blog',
				'blog_urls' => "  https://example.com/a  ,\n\nhttps://example.com/b\n,",
			)
		);

		$this->assertSame(
			array( 'https://example.com/a', 'https://example.com/b' ),
			$settings->blog_urls()
		);
	}

	/**
	 * CSV-imported URLs merge with the textarea list by default.
	 *
	 * @return void
	 */
	public function test_csv_urls_merge_with_textarea(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'mode'      => 'blog',
				'blog_urls' => 'https://example.com/typed',
			),
			array( 'https://example.com/csv' )
		);

		$this->assertSame(
			array( 'https://example.com/typed', 'https://example.com/csv' ),
			$settings->blog_urls()
		);
	}

	/**
	 * With the replace box ticked, CSV URLs replace the textarea list.
	 *
	 * @return void
	 */
	public function test_csv_urls_replace_when_box_ticked(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'mode'                  => 'blog',
				'blog_urls'             => 'https://example.com/typed',
				'blog_urls_csv_replace' => '1',
			),
			array( 'https://example.com/csv' )
		);

		$this->assertSame( array( 'https://example.com/csv' ), $settings->blog_urls() );
	}

	/**
	 * The replace box has no effect when no CSV was uploaded (keeps the textarea).
	 *
	 * @return void
	 */
	public function test_replace_box_no_op_without_csv(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'mode'                  => 'blog',
				'blog_urls'             => 'https://example.com/typed',
				'blog_urls_csv_replace' => '1',
			),
			array()
		);

		$this->assertSame( array( 'https://example.com/typed' ), $settings->blog_urls() );
	}

	/**
	 * Out-of-range numbers are clamped by the value object, not the parser.
	 *
	 * @return void
	 */
	public function test_numbers_are_clamped(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'mode'             => 'blog',
				'pagination_limit' => '-2',
			)
		);

		$this->assertSame( 1, $settings->pagination_limit() );
		$this->assertSame( PaginationStyle::Leading, $settings->pagination_style() );
	}

	public function test_full_archive_pagination_defaults_off_and_parses_checkbox(): void {
		$parser = new SettingsFormParser();

		$this->assertFalse( $parser->parse( array() )->full_archive_pagination() );
		$this->assertTrue( $parser->parse( array( 'full_archive_pagination' => '1' ) )->full_archive_pagination() );
	}

	/**
	 * Theme controls map through to the settings model.
	 *
	 * @return void
	 */
	public function test_theme_controls_are_parsed(): void {
		$settings = ( new SettingsFormParser() )->parse(
			array(
				'theme_layout'        => 'list',
				'theme_accent_color'  => '#112233',
				'theme_surface_color' => '#fafafa',
				'theme_text_color'    => '#222222',
				'theme_border_color'  => '#cccccc',
			)
		);

		$this->assertSame( 'list', $settings->theme()->layout() );
		$this->assertSame( '#112233', $settings->theme()->accent_color() );
		$this->assertSame( '#fafafa', $settings->theme()->surface_color() );
		$this->assertSame( '#222222', $settings->theme()->text_color() );
		$this->assertSame( '#cccccc', $settings->theme()->border_color() );
	}
}
