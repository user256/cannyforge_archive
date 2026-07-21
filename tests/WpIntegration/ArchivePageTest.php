<?php
/**
 * Integration test: the archive endpoint renders real WordPress content.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration;

use CannyForge\Archive\Tests\WpIntegration\Support\Http;
use CannyForge\Archive\Tests\WpIntegration\Support\WpEnvCli;
use PHPUnit\Framework\TestCase;

/**
 * Verifies `/archive/` renders the seeded historic posts against a real,
 * disposable WordPress instance (ticket 603) — the first item of the manual
 * smoke checklist in README.md.
 *
 * Relies on scripts/run-integration-tests.sh having already seeded content via
 * scripts/seed-historic-content.sh and set a permalink structure that
 * activates the plugin's rewrite endpoint.
 */
final class ArchivePageTest extends TestCase {
	/**
	 * `/archive/` responds 200 and its body contains seeded post titles.
	 *
	 * @return void
	 */
	public function test_archive_endpoint_renders_seeded_historic_posts(): void {
		$response = Http::get( WpEnvCli::base_url() . '/archive/' );

		$this->assertSame( 200, $response['status'] );
		$this->assertStringContainsString( 'Archive Test Story', $response['body'] );
	}

	/**
	 * The archive page is a themed HTML document (not a bare fragment) — the
	 * renderer runs inside `get_header()` / `get_footer()`, so `wp_head` fires
	 * and the plugin's own stylesheet/inline theme variables are present.
	 *
	 * @return void
	 */
	public function test_archive_endpoint_includes_theme_assets(): void {
		$response = Http::get( WpEnvCli::base_url() . '/archive/' );

		$this->assertStringContainsString( '<html', $response['body'] );
		$this->assertStringContainsString( 'cannyforge-archive', $response['body'] );
	}
}
