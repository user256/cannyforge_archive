<?php
/**
 * Tests for whole-database archive filter options.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Core\Archive\FilterOptionsProvider;
use PHPUnit\Framework\TestCase;

/**
 * Month options use a distinct, cached database result rather than all posts.
 */
final class FilterOptionsProviderTest extends TestCase {
	/**
	 * Reset the database fixture and object cache before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wpdb']->queries                       = array();
		$GLOBALS['cannyforge_test_wpdb_get_col_result'] = array();
		$GLOBALS['cannyforge_test_object_cache']        = array();
		$GLOBALS['cannyforge_test_cache_last_changed']  = array( 'posts' => '1' );
		$GLOBALS['cannyforge_test_get_posts_args']      = array();
	}

	/**
	 * Month options come from one distinct query and reject malformed values.
	 *
	 * @return void
	 */
	public function test_months_use_distinct_year_month_query(): void {
		$GLOBALS['cannyforge_test_wpdb_get_col_result'] = array( '2026-07', '2026-06', 'invalid', '2026-06' );

		$options = ( new FilterOptionsProvider() )->months();

		$this->assertSame(
			array(
				array(
					'value' => '2026-07',
					'label' => 'Jul 2026',
				),
				array(
					'value' => '2026-06',
					'label' => 'Jun 2026',
				),
			),
			$options
		);
		$this->assertCount( 1, $GLOBALS['wpdb']->queries );
		$this->assertStringContainsString( 'SELECT DISTINCT DATE_FORMAT', $GLOBALS['wpdb']->queries[0] );
		$this->assertSame( array(), $GLOBALS['cannyforge_test_get_posts_args'] );
	}

	/**
	 * Repeated requests within the posts generation use the object cache.
	 *
	 * @return void
	 */
	public function test_months_are_cached_until_posts_change(): void {
		$GLOBALS['cannyforge_test_wpdb_get_col_result'] = array( '2026-07' );
		$provider                                       = new FilterOptionsProvider();

		$provider->months();
		$provider->months();
		$this->assertCount( 1, $GLOBALS['wpdb']->queries );

		$GLOBALS['cannyforge_test_cache_last_changed']['posts'] = '2';
		$provider->months();
		$this->assertCount( 2, $GLOBALS['wpdb']->queries );
	}
}
