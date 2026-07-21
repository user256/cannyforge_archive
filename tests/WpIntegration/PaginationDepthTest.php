<?php
/**
 * Integration test: the pagination replacement on a deep category archive.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration;

use CannyForge\Archive\Tests\WpIntegration\Support\Http;
use CannyForge\Archive\Tests\WpIntegration\Support\WpEnvCli;
use PHPUnit\Framework\TestCase;

/**
 * Verifies a deep (multi-page) category archive shows the shortened
 * pagination block with its "View Archive" link, replacing the theme's
 * default paginated tail, against real WordPress (ticket 603).
 *
 * Requires the seeded category to genuinely span more than one page at the
 * site's configured `posts_per_page`; this is asserted explicitly so a future
 * change to the seed count (or the site's page size) fails loudly here rather
 * than silently testing nothing.
 */
final class PaginationDepthTest extends TestCase {
	/**
	 * A category slug seeded by scripts/seed-historic-content.sh.
	 */
	private const CATEGORY_SLUG = 'case-studies';

	/**
	 * Visiting page 2 of a category with more posts than the site's page size
	 * replaces the default pagination with the shortened block and its
	 * "View Archive" link.
	 *
	 * @return void
	 */
	public function test_deep_category_archive_shows_shortened_pagination(): void {
		$prefix         = WpEnvCli::run( 'db', 'prefix' );
		$posts_per_page = (int) WpEnvCli::run( 'option', 'get', 'posts_per_page' );
		$category_count = (int) WpEnvCli::scalar_query(
			sprintf(
				'SELECT COUNT(DISTINCT p.ID) FROM %1$sposts p ' .
				'JOIN %1$sterm_relationships tr ON tr.object_id = p.ID ' .
				'JOIN %1$sterm_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id ' .
				'JOIN %1$sterms t ON t.term_id = tt.term_id ' .
				"WHERE p.post_status='publish' AND p.post_type='post' AND tt.taxonomy='category' AND t.slug='%2\$s';",
				$prefix,
				self::CATEGORY_SLUG
			)
		);

		$this->assertGreaterThan(
			$posts_per_page,
			$category_count,
			sprintf(
				'Seeded category "%s" has only %d posts at posts_per_page=%d; not deep enough to exercise the pagination replacement. Increase the seed count.',
				self::CATEGORY_SLUG,
				$category_count,
				$posts_per_page
			)
		);

		$response = Http::get( WpEnvCli::base_url() . '/category/' . self::CATEGORY_SLUG . '/page/2/' );

		$this->assertSame( 200, $response['status'] );
		$this->assertStringContainsString( 'cannyforge-pagination', $response['body'] );
		$this->assertStringContainsString( 'cannyforge-pagination__archive', $response['body'] );
		$this->assertStringContainsString(
			'href="' . WpEnvCli::base_url() . '/archive/"',
			$response['body'],
			'The "View Archive" link should point at the archive endpoint.'
		);

		// pagination_limit defaults to 1 (Leading style): only page 1 is ever
		// shown as a numbered link, however deep the request. A page-2 number
		// appearing here would mean the shortening is not actually applied.
		$this->assertDoesNotMatchRegularExpression(
			'/cannyforge-pagination__page[^>]*>2</',
			$response['body']
		);
	}
}
