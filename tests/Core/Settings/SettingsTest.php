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
use CannyForge\Archive\Contracts\Settings\Theme;
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
		$this->assertSame( 50, $settings->news_fallback_count() );
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
		$this->assertSame( Theme::LAYOUT_CARDS, $settings->theme()->layout() );
		$this->assertSame( '#6d4aff', $settings->theme()->accent_color() );
	}

	/**
	 * Export then import via the array form is lossless.
	 *
	 * @return void
	 */
	public function test_round_trips_through_array_core(): void {
		$original = Settings::from_array(
			array(
				'mode'                => 'news',
				'pagination_limit'    => 5,
				'news_window_hours'   => 48,
				'news_fallback_count' => 30,
				'blog_max_urls'       => 25,
				'blog_urls'           => array( 'https://example.com/a' ),
				'archive_url'         => 'https://example.com/all/',
			)
		);

		$restored = Settings::from_array( $original->to_array() );

		$this->assertSame( Mode::News, $restored->mode() );
		$this->assertSame( 5, $restored->pagination_limit() );
		$this->assertSame( 48, $restored->news_window_hours() );
		$this->assertSame( 30, $restored->news_fallback_count() );
		$this->assertSame( 25, $restored->blog_max_urls() );
		$this->assertSame( array( 'https://example.com/a' ), $restored->blog_urls() );
		$this->assertSame( 'https://example.com/all/', $restored->archive_url() );
	}

	/**
	 * Export then import via the array form is lossless for nested objects.
	 *
	 * @return void
	 */
	public function test_round_trips_through_array_nested(): void {
		$original = Settings::from_array(
			array(
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
				'targeting'         => array(
					'category' => false,
					'tag'      => true,
					'author'   => true,
					'date'     => true,
				),
				'seo'               => array(
					'title'            => 'Archive',
					'meta_description' => 'All our stories.',
					'index'            => false,
					'follow'           => true,
					'canonical'        => 'https://example.com/canonical/',
				),
				'theme'             => array(
					'layout'        => 'list',
					'accent_color'  => '#112233',
					'surface_color' => '#fefefe',
					'text_color'    => '#222222',
					'border_color'  => '#cccccc',
				),
				'content_selection' => array(
					'include_categories' => array( 'News' ),
					'exclude_tags'       => array( 'spoiler' ),
					'exclude_noindex'    => true,
					'pinned_urls'        => array( 'https://example.com/pin/' ),
				),
			)
		);

		$restored = Settings::from_array( $original->to_array() );

		$this->assertTrue( $restored->link_types()->description() );
		$this->assertTrue( $restored->filters()->author() );
		$this->assertFalse( $restored->targeting()->category() );
		$this->assertTrue( $restored->targeting()->author() );
		$this->assertSame( 'noindex,follow', $restored->seo()->robots() );
		$this->assertSame( 'https://example.com/canonical/', $restored->seo()->canonical() );
		$this->assertSame( Theme::LAYOUT_LIST, $restored->theme()->layout() );
		$this->assertSame( '#112233', $restored->theme()->accent_color() );
		$this->assertSame( array( 'News' ), $restored->content_selection()->include_categories() );
		$this->assertTrue( $restored->content_selection()->exclude_noindex() );
		$this->assertSame( array( 'https://example.com/pin/' ), $restored->content_selection()->pinned_urls() );
	}

	/**
	 * Out-of-range integers are clamped rather than rejected.
	 *
	 * @return void
	 */
	public function test_clamps_out_of_range_integers(): void {
		$settings = Settings::from_array(
			array(
				'pagination_limit'    => -4,
				'news_window_hours'   => 0,
				'blog_max_urls'       => -1,
				'news_fallback_count' => 0,
			)
		);

		$this->assertSame( 1, $settings->pagination_limit() );
		$this->assertSame( 1, $settings->news_window_hours() );
		$this->assertSame( 1, $settings->blog_max_urls() );
		$this->assertSame( 1, $settings->news_fallback_count() );
	}

	/**
	 * The News fallback count is clamped to a 500 upper bound so it can never
	 * exceed the windowed entry cap.
	 *
	 * @return void
	 */
	public function test_news_fallback_count_clamps_to_upper_bound(): void {
		$settings = Settings::from_array( array( 'news_fallback_count' => 99999 ) );

		$this->assertSame( 500, $settings->news_fallback_count() );
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
				'theme'      => 'still-not-an-array',
			)
		);

		$this->assertTrue( $settings->link_types()->title() );
		$this->assertTrue( $settings->filters()->search() );
		$this->assertSame( array(), $settings->blog_urls() );
		$this->assertSame( Theme::LAYOUT_CARDS, $settings->theme()->layout() );
	}

	/**
	 * Invalid theme values fall back to safe defaults.
	 *
	 * @return void
	 */
	public function test_invalid_theme_values_fall_back_to_defaults(): void {
		$settings = Settings::from_array(
			array(
				'theme' => array(
					'layout'        => 'mosaic',
					'accent_color'  => 'blue',
					'surface_color' => '#fff',
					'text_color'    => '#XYZXYZ',
					'border_color'  => '#123456',
				),
			)
		);

		$this->assertSame( Theme::LAYOUT_CARDS, $settings->theme()->layout() );
		$this->assertSame( '#6d4aff', $settings->theme()->accent_color() );
		$this->assertSame( '#fff', $settings->theme()->surface_color() );
		$this->assertSame( '#1b143f', $settings->theme()->text_color() );
		$this->assertSame( '#123456', $settings->theme()->border_color() );
	}
}
