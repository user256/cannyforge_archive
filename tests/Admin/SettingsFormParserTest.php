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
				'mode'              => 'news',
				'pagination_limit'  => '4',
				'news_window_hours' => '36',
				'blog_max_urls'     => '50',
				'link_title'        => '1',
				'link_description'  => '1',
				'filter_search'     => '1',
				'filter_author'     => '1',
				'blog_urls'         => "https://example.com/a\nhttps://example.com/b",
			)
		);

		$this->assertSame( Mode::News, $settings->mode() );
		$this->assertSame( 4, $settings->pagination_limit() );
		$this->assertSame( 36, $settings->news_window_hours() );
		$this->assertTrue( $settings->link_types()->title() );
		$this->assertTrue( $settings->link_types()->description() );
		$this->assertFalse( $settings->link_types()->featured_image() );
		$this->assertTrue( $settings->filters()->search() );
		$this->assertTrue( $settings->filters()->author() );
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
		$this->assertFalse( $settings->filters()->search() );
		$this->assertFalse( $settings->filters()->author() );
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
	}
}
