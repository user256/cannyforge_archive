<?php
/**
 * Integration coverage for the optional server-rendered full archive.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration;

use CannyForge\Archive\Tests\WpIntegration\Support\Http;
use CannyForge\Archive\Tests\WpIntegration\Support\WpEnvCli;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the rewrite endpoint against the disposable real WordPress site.
 */
final class FullArchivePaginationTest extends TestCase {
	private const SETTINGS_OPTION = 'cannyforge_archive_settings';

	/**
	 * The selected first page stays compact, while `/archive/page/2/` renders
	 * the remaining local posts without JavaScript.
	 *
	 * @return void
	 */
	public function test_enabled_continuation_is_server_rendered_and_excludes_page_one_post(): void {
		$promoted_slug = 'archive-test-story-0001';
		$promoted_url  = WpEnvCli::base_url() . '/' . $promoted_slug . '/';
		$this->enable_with_promoted_url( $promoted_url );

		try {
			$page_one             = Http::get( WpEnvCli::base_url() . '/archive/' );
			$page_two             = Http::get( WpEnvCli::base_url() . '/archive/page/2/' );
			$page_three           = Http::get( WpEnvCli::base_url() . '/archive/page/3/' );
			$published_post_count = (int) WpEnvCli::scalar_query(
				'SELECT COUNT(*) FROM ' . WpEnvCli::run( 'db', 'prefix' ) . "posts WHERE post_type = 'post' AND post_status = 'publish';"
			);
			$continuation_count   = $published_post_count - 1;

			$this->assertSame( 200, $page_one['status'] );
			$this->assertStringContainsString( 'Archive Test Story 0001', $page_one['body'] );
			$this->assertStringContainsString( '/archive/page/2/', $page_one['body'] );

			$this->assertSame( 200, $page_two['status'] );
			$this->assertStringContainsString( 'Archive page 2', $page_two['body'] );
			$this->assertStringContainsString( 'Archive Test Story 0002', $page_two['body'] );
			$this->assertStringNotContainsString( 'Archive Test Story 0001', $page_two['body'] );
			$this->assertStringContainsString( 'cannyforge-archive__list', $page_two['body'] );
			$this->assertGreaterThan( 50, $continuation_count, 'The integration seed must cross a continuation-page boundary.' );
			$this->assertSame( 50, substr_count( $page_two['body'], 'cannyforge-archive__item' ) );

			$this->assertSame( 200, $page_three['status'] );
			$this->assertStringContainsString( 'Archive page 3', $page_three['body'] );
			$this->assertStringContainsString( 'Archive Test Story 0051', $page_three['body'] );
			$this->assertStringNotContainsString( 'Archive Test Story 0051', $page_two['body'] );
			$this->assertStringNotContainsString( 'Archive Test Story 0001', $page_three['body'] );
			$this->assertSame( $continuation_count - 50, substr_count( $page_three['body'], 'cannyforge-archive__item' ) );
		} finally {
			WpEnvCli::run( 'option', 'delete', self::SETTINGS_OPTION );
		}
	}

	/**
	 * Page one has one canonical URL; exhausted continuation pages stay 404.
	 *
	 * @return void
	 */
	public function test_page_one_redirects_and_out_of_range_continuation_is_404(): void {
		$this->enable_with_promoted_url( WpEnvCli::base_url() . '/archive-test-story-0001/' );

		try {
			$page_one_alias = Http::get( WpEnvCli::base_url() . '/archive/page/1/', false );
			$out_of_range   = Http::get( WpEnvCli::base_url() . '/archive/page/999/', false );

			$this->assertSame( 301, $page_one_alias['status'] );
			$this->assertSame( 404, $out_of_range['status'] );
		} finally {
			WpEnvCli::run( 'option', 'delete', self::SETTINGS_OPTION );
		}
	}

	/**
	 * Inclusion and noindex rules are applied by the bounded database query.
	 *
	 * The seeded stories rotate categories every four posts: 0001, 0005, 0009
	 * and 0013 are Evergreen Guides. Page one promotes 0001; 0005 uses Yoast's
	 * flag and 0009 Rank Math's serialized flag, leaving 0013 as a
	 * representative continuation result.
	 *
	 * @return void
	 */
	public function test_continuation_applies_selection_rules_before_paging(): void {
		$promoted_url  = WpEnvCli::base_url() . '/archive-test-story-0001/';
		$yoast_noindex = WpEnvCli::run( 'post', 'list', '--name=archive-test-story-0005', '--post_type=post', '--field=ID' );
		$rank_noindex  = WpEnvCli::run( 'post', 'list', '--name=archive-test-story-0009', '--post_type=post', '--field=ID' );
		$this->assertNotSame( '', $yoast_noindex );
		$this->assertNotSame( '', $rank_noindex );

		WpEnvCli::run( 'post', 'meta', 'update', $yoast_noindex, '_yoast_wpseo_meta-robots-noindex', '1' );
		WpEnvCli::run( 'eval', 'update_post_meta( ' . (int) $rank_noindex . ", 'rank_math_robots', array( 'noindex' ) );" );
		$this->enable_with_promoted_url(
			$promoted_url,
			array(
				'include_categories' => array( 'Evergreen Guides' ),
				'exclude_noindex'    => true,
			)
		);

		try {
			$page_two = Http::get( WpEnvCli::base_url() . '/archive/page/2/' );

			$this->assertSame( 200, $page_two['status'] );
			$this->assertStringContainsString( 'Archive Test Story 0013', $page_two['body'] );
			$this->assertStringNotContainsString( 'Archive Test Story 0002', $page_two['body'] );
			$this->assertStringNotContainsString( 'Archive Test Story 0005', $page_two['body'] );
			$this->assertStringNotContainsString( 'Archive Test Story 0009', $page_two['body'] );
		} finally {
			WpEnvCli::run( 'post', 'meta', 'delete', $yoast_noindex, '_yoast_wpseo_meta-robots-noindex' );
			WpEnvCli::run( 'post', 'meta', 'delete', $rank_noindex, 'rank_math_robots' );
			WpEnvCli::run( 'option', 'delete', self::SETTINGS_OPTION );
		}
	}

	/**
	 * Set the minimal explicit configuration used by this feature test.
	 *
	 * @param string               $promoted_url Local URL selected for the compact first page.
	 * @param array<string, mixed> $selection    Optional content-selection rules.
	 * @return void
	 */
	private function enable_with_promoted_url( string $promoted_url, array $selection = array() ): void {
		$settings = array(
			'mode'                    => 'blog',
			'blog_max_urls'           => 1,
			'blog_urls'               => array( $promoted_url ),
			'full_archive_pagination' => true,
			'content_selection'       => $selection,
		);

		$json = json_encode( $settings );
		$this->assertIsString( $json );
		WpEnvCli::run( 'option', 'update', self::SETTINGS_OPTION, $json, '--format=json' );
	}
}
