<?php
/**
 * Tests for the Settings value object.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Settings;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Defaults, round-trip, and coercion of the settings model.
 */
class SettingsTest extends TestCase {
	/**
	 * Brief defaults are produced by a bare construction.
	 *
	 * @return void
	 */
	public function test_defaults_match_the_brief(): void {
		$settings = new Settings();

		$this->assertSame( Mode::Blog, $settings->mode() );
		$this->assertSame( 1, $settings->pagination_limit() );
		$this->assertSame( 72, $settings->news_window_hours() );
		$this->assertSame( 100, $settings->blog_max_urls() );
		$this->assertTrue( $settings->link_types()->title() );
		$this->assertFalse( $settings->link_types()->description() );
		$this->assertFalse( $settings->link_types()->featured_image() );
		$this->assertTrue( $settings->filters()->search() );
		$this->assertFalse( $settings->filters()->author() );
		$this->assertTrue( $settings->targeting()->category() );
		$this->assertTrue( $settings->targeting()->tag() );
		$this->assertFalse( $settings->targeting()->author() );
		$this->assertFalse( $settings->targeting()->date() );
	}

	/**
	 * Export then import via the array form is lossless.
	 *
	 * @return void
	 */
	public function test_round_trips_through_array(): void {
		$original = Settings::from_array(
			array(
				'mode'              => 'news',
				'pagination_limit'  => 5,
				'link_types'        => array(
					'title'          => true,
					'description'    => true,
					'featured_image' => true,
				),
				'filters'           => array(
					'search'     => false,
					'category'   => true,
					'tag'        => false,
					'month_year' => true,
					'author'     => true,
				),
				'news_window_hours' => 48,
				'blog_max_urls'     => 25,
				'blog_urls'         => array( 'https://example.com/a' ),
				'targeting'         => array(
					'category' => false,
					'tag'      => true,
					'author'   => true,
					'date'     => true,
				),
			)
		);

		$restored = Settings::from_array( $original->to_array() );

		$this->assertEquals( $original->to_array(), $restored->to_array() );
		$this->assertSame( Mode::News, $restored->mode() );
		$this->assertTrue( $restored->link_types()->description() );
		$this->assertTrue( $restored->filters()->author() );
		$this->assertFalse( $restored->targeting()->category() );
		$this->assertTrue( $restored->targeting()->author() );
	}

	/**
	 * Out-of-range integers are clamped rather than rejected.
	 *
	 * @return void
	 */
	public function test_clamps_out_of_range_integers(): void {
		$settings = Settings::from_array(
			array(
				'pagination_limit'  => -4,
				'news_window_hours' => 0,
				'blog_max_urls'     => -1,
			)
		);

		$this->assertSame( 1, $settings->pagination_limit() );
		$this->assertSame( 1, $settings->news_window_hours() );
		$this->assertSame( 1, $settings->blog_max_urls() );
	}

	/**
	 * Unknown modes fall back to Blog.
	 *
	 * @return void
	 */
	public function test_unknown_mode_falls_back_to_blog(): void {
		$this->assertSame( Mode::Blog, Settings::from_array( array( 'mode' => 'wat' ) )->mode() );
	}

	/**
	 * Blog URLs are trimmed, de-duplicated, emptied-out, and capped.
	 *
	 * @return void
	 */
	public function test_blog_urls_are_cleaned_and_capped(): void {
		$settings = Settings::from_array(
			array(
				'blog_max_urls' => 2,
				'blog_urls'     => array(
					'  https://example.com/a  ',
					'https://example.com/a',
					'',
					'https://example.com/b',
					'https://example.com/c',
				),
			)
		);

		$this->assertSame(
			array( 'https://example.com/a', 'https://example.com/b' ),
			$settings->blog_urls()
		);
	}

	/**
	 * Garbage nested values degrade to defaults instead of throwing.
	 *
	 * @return void
	 */
	public function test_tolerates_garbage_nested_values(): void {
		$settings = Settings::from_array(
			array(
				'link_types' => 'not-an-array',
				'filters'    => 42,
				'blog_urls'  => 'also-not-an-array',
			)
		);

		$this->assertTrue( $settings->link_types()->title() );
		$this->assertTrue( $settings->filters()->search() );
		$this->assertSame( array(), $settings->blog_urls() );
	}
}
