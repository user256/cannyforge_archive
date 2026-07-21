<?php
/**
 * Integration test: the admin-ajax archive search endpoint against real WordPress.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration;

use CannyForge\Archive\Frontend\ArchiveSearchEndpoint;
use CannyForge\Archive\Tests\WpIntegration\Support\Http;
use CannyForge\Archive\Tests\WpIntegration\Support\WpEnvCli;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the whole-database search/filter AJAX endpoint
 * ({@see ArchiveSearchEndpoint}) returns correct JSON for a search term and
 * for each filter type, against real WordPress and real seeded content
 * (ticket 603). The unit suite (ticket 601) pins the endpoint's own
 * sanitisation/shape contract against shims; this suite proves the same
 * behaviour holds once a real `WP_Query` and a real nonce are in play.
 *
 * Every case cross-checks the endpoint's reported `total` against an
 * independent count taken directly from the database, rather than a
 * hand-derived expectation, so the assertions stay correct regardless of the
 * exact seed content/count in use.
 */
final class AdminAjaxSearchTest extends TestCase {
	/**
	 * A valid nonce for the search action, fetched once for the class.
	 *
	 * @var string
	 */
	private static string $nonce;

	/**
	 * The WordPress table prefix, fetched once for the class.
	 *
	 * @var string
	 */
	private static string $prefix;

	/**
	 * Fetch a real nonce and the DB table prefix from the running instance.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$nonce  = WpEnvCli::run( 'eval', sprintf( 'echo wp_create_nonce(%s);', var_export( ArchiveSearchEndpoint::NONCE, true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- exporting a PHP literal into a remote `wp eval` snippet, not leftover debug output.
		self::$prefix = WpEnvCli::run( 'db', 'prefix' );

		self::assertNotSame( '', self::$nonce, 'Could not obtain a search nonce from the running WordPress instance.' );
	}

	/**
	 * A free-text search returns only the posts that actually match, and the
	 * JSON shape matches the JS contract in assets/js/archive-filters.js.
	 *
	 * @return void
	 */
	public function test_search_term_returns_matching_results(): void {
		$term     = 'crawl budget';
		$expected = (int) WpEnvCli::scalar_query(
			sprintf(
				"SELECT COUNT(*) FROM %sposts WHERE post_status='publish' AND post_type='post' AND (post_title LIKE '%%%s%%' OR post_content LIKE '%%%s%%' OR post_excerpt LIKE '%%%s%%');",
				self::$prefix,
				$term,
				$term,
				$term
			)
		);

		$data = $this->search( array( 'search' => $term ) );

		$this->assertGreaterThan( 0, $expected, 'Fixture data does not contain the expected search term; seeding likely failed.' );
		$this->assertSame( $expected, $data['total'] );
		$this->assertIsString( $data['html'] );
		$this->assertArrayHasKey( 'page', $data );
		$this->assertArrayHasKey( 'per_page', $data );
		$this->assertArrayHasKey( 'total_pages', $data );
		$this->assertArrayHasKey( 'has_next', $data );
		$this->assertArrayHasKey( 'has_prev', $data );
		$this->assertTrue( $data['is_active'] );
	}

	/**
	 * The category filter narrows to that category only.
	 *
	 * @return void
	 */
	public function test_category_filter_returns_only_that_category(): void {
		$slug     = 'case-studies';
		$expected = (int) WpEnvCli::scalar_query(
			sprintf(
				'SELECT COUNT(DISTINCT p.ID) FROM %1$sposts p ' .
				'JOIN %1$sterm_relationships tr ON tr.object_id = p.ID ' .
				'JOIN %1$sterm_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id ' .
				'JOIN %1$sterms t ON t.term_id = tt.term_id ' .
				"WHERE p.post_status='publish' AND p.post_type='post' AND tt.taxonomy='category' AND t.slug='%2\$s';",
				self::$prefix,
				$slug
			)
		);

		$data = $this->search(
			array(
				'category' => $slug,
				'per_page' => 100,
			)
		);

		$this->assertGreaterThan( 0, $expected, 'Fixture data has no posts in the expected seeded category.' );
		$this->assertSame( $expected, $data['total'] );
		$this->assertResultsAllHaveAttribute( $data['html'], 'data-categories', 'Case Studies' );
	}

	/**
	 * The tag filter narrows to that tag only.
	 *
	 * @return void
	 */
	public function test_tag_filter_returns_only_that_tag(): void {
		$slug     = 'internal-links';
		$expected = (int) WpEnvCli::scalar_query(
			sprintf(
				'SELECT COUNT(DISTINCT p.ID) FROM %1$sposts p ' .
				'JOIN %1$sterm_relationships tr ON tr.object_id = p.ID ' .
				'JOIN %1$sterm_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id ' .
				'JOIN %1$sterms t ON t.term_id = tt.term_id ' .
				"WHERE p.post_status='publish' AND p.post_type='post' AND tt.taxonomy='post_tag' AND t.slug='%2\$s';",
				self::$prefix,
				$slug
			)
		);

		$data = $this->search(
			array(
				'tag'      => $slug,
				'per_page' => 100,
			)
		);

		$this->assertGreaterThan( 0, $expected, 'Fixture data has no posts with the expected seeded tag.' );
		$this->assertSame( $expected, $data['total'] );
		$this->assertResultsAllHaveAttribute( $data['html'], 'data-tags', 'internal-links' );
	}

	/**
	 * The author filter narrows to that author only.
	 *
	 * @return void
	 */
	public function test_author_filter_returns_only_that_author(): void {
		$nicename = 'archive-tester-one';
		$expected = (int) WpEnvCli::scalar_query(
			sprintf(
				'SELECT COUNT(*) FROM %1$sposts p JOIN %1$susers u ON u.ID = p.post_author ' .
				"WHERE p.post_status='publish' AND p.post_type='post' AND u.user_nicename='%2\$s';",
				self::$prefix,
				$nicename
			)
		);

		$data = $this->search(
			array(
				'author'   => $nicename,
				'per_page' => 100,
			)
		);

		$this->assertGreaterThan( 0, $expected, 'Fixture data has no posts by the expected seeded author.' );
		$this->assertSame( $expected, $data['total'] );
	}

	/**
	 * The month filter narrows to that publication month only, proving old,
	 * non-promoted content is genuinely reachable (ticket 301's promise).
	 *
	 * @return void
	 */
	public function test_month_filter_returns_only_that_month(): void {
		$month = WpEnvCli::scalar_query(
			sprintf(
				"SELECT DATE_FORMAT(post_date,'%%Y-%%m') FROM %sposts WHERE post_status='publish' AND post_type='post' AND post_title LIKE 'Archive Test Story%%' ORDER BY post_date ASC LIMIT 1;",
				self::$prefix
			)
		);

		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}$/', $month, 'Could not determine a seeded post month; seeding likely failed.' );

		$expected = (int) WpEnvCli::scalar_query(
			sprintf(
				"SELECT COUNT(*) FROM %sposts WHERE post_status='publish' AND post_type='post' AND DATE_FORMAT(post_date,'%%Y-%%m')='%s';",
				self::$prefix,
				$month
			)
		);

		$data = $this->search(
			array(
				'month'    => $month,
				'per_page' => 100,
			)
		);

		$this->assertGreaterThan( 0, $expected );
		$this->assertSame( $expected, $data['total'] );
	}

	/**
	 * An invalid/missing nonce is rejected, and clamps hostile input rather
	 * than passing it straight through (ticket 601's promises, re-verified
	 * against a real `check_ajax_referer()`).
	 *
	 * @return void
	 */
	public function test_invalid_nonce_is_rejected(): void {
		$response = Http::post(
			WpEnvCli::base_url() . '/wp-admin/admin-ajax.php',
			array(
				'action' => ArchiveSearchEndpoint::ACTION,
				'nonce'  => 'not-a-real-nonce',
				'search' => 'anything',
			)
		);

		$this->assertSame( 403, $response['status'] );
	}

	/**
	 * A hostile oversized `per_page` is clamped to the documented maximum
	 * rather than honoured verbatim.
	 *
	 * @return void
	 */
	public function test_hostile_per_page_is_clamped(): void {
		$data = $this->search( array( 'per_page' => 999999 ) );

		$this->assertLessThanOrEqual( 100, $data['per_page'] );
	}

	/**
	 * Issue a search AJAX request and decode its JSON `data` payload.
	 *
	 * @param array<string, mixed> $params Extra request parameters.
	 * @return array<string, mixed>
	 */
	private function search( array $params ): array {
		$response = Http::post(
			WpEnvCli::base_url() . '/wp-admin/admin-ajax.php',
			array_merge(
				array(
					'action' => ArchiveSearchEndpoint::ACTION,
					'nonce'  => self::$nonce,
				),
				$params
			)
		);

		$this->assertSame( 200, $response['status'], 'Request body: ' . $response['body'] );

		$payload = json_decode( $response['body'], true );

		$this->assertIsArray( $payload );
		$this->assertTrue( $payload['success'] ?? false, 'Request body: ' . $response['body'] );
		$this->assertIsArray( $payload['data'] );

		return $payload['data'];
	}

	/**
	 * Assert every rendered result item carries the expected value for a
	 * `data-*` attribute — a stronger check than the total count alone, since
	 * it would also catch a filter that matched the right *number* of posts
	 * for the wrong reason.
	 *
	 * @param string $html      The rendered result-list HTML.
	 * @param string $attribute The attribute name (e.g. 'data-categories').
	 * @param string $needle    A substring every occurrence must contain.
	 * @return void
	 */
	private function assertResultsAllHaveAttribute( string $html, string $attribute, string $needle ): void {
		$matches = array();
		$found   = preg_match_all( '/' . preg_quote( $attribute, '/' ) . '="([^"]*)"/', $html, $matches );

		$this->assertGreaterThan( 0, $found, "No {$attribute} attributes found in the rendered result list." );

		foreach ( $matches[1] as $value ) {
			$this->assertStringContainsString( $needle, $value );
		}
	}
}
