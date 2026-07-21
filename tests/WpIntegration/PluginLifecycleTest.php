<?php
/**
 * Integration test: activate/deactivate/reactivate leaves no rewrite residue.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\WpIntegration;

use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Tests\WpIntegration\Support\Http;
use CannyForge\Archive\Tests\WpIntegration\Support\WpEnvCli;
use PHPUnit\Framework\TestCase;

/**
 * Verifies ticket 201's lifecycle guarantee against real WordPress: plugin
 * activation registers the archive rewrite endpoint and flushes rewrite
 * rules; deactivation flushes them again, leaving no stale rule behind; and
 * reactivating restores exactly the same rule the plugin started with — never
 * duplicated, never missing (ticket 603).
 *
 * "No residue" is checked concretely against the `rewrite_rules` option: the
 * number of rules mapping to the plugin's query var
 * ({@see ArchivePage::QUERY_VAR}) must be zero while deactivated, and must
 * match the pre-test baseline once reactivated.
 *
 * Guarantees the plugin ends the test active regardless of assertion
 * outcome, so a failure here does not cascade into every other test in the
 * suite (which assume the plugin is active).
 */
final class PluginLifecycleTest extends TestCase {
	private const PLUGIN_SLUG = 'cannyforge-archive';

	/**
	 * Activate → deactivate → reactivate leaves the archive rewrite rule
	 * present exactly once, absent in between, and the endpoint serving again.
	 *
	 * @return void
	 */
	public function test_activate_deactivate_reactivate_leaves_no_rewrite_residue(): void {
		try {
			WpEnvCli::run( 'plugin', 'activate', self::PLUGIN_SLUG );
			$baseline = $this->archive_rule_count();

			$this->assertSame(
				1,
				$baseline,
				'Expected exactly one rewrite rule for the archive endpoint after activation.'
			);

			WpEnvCli::run( 'plugin', 'deactivate', self::PLUGIN_SLUG );
			$this->assertSame(
				0,
				$this->archive_rule_count(),
				'A stale archive rewrite rule survived deactivation — ticket 201 residue regression.'
			);

			WpEnvCli::run( 'plugin', 'activate', self::PLUGIN_SLUG );
			$this->assertSame(
				$baseline,
				$this->archive_rule_count(),
				'Reactivation produced a different rule count than the original activation (duplication or loss).'
			);

			$response = Http::get( WpEnvCli::base_url() . '/archive/' );
			$this->assertSame( 200, $response['status'], 'The archive endpoint should serve again after reactivation.' );
		} finally {
			// Leave the plugin active no matter what, so later tests in the
			// suite (which assume it is) are not affected by a failure here.
			WpEnvCli::run( 'plugin', 'activate', self::PLUGIN_SLUG );
		}
	}

	/**
	 * The number of rewrite rules mapping to the archive endpoint's query var.
	 *
	 * @return int
	 */
	private function archive_rule_count(): int {
		$json = WpEnvCli::run( 'option', 'get', 'rewrite_rules', '--format=json' );

		if ( '' === $json ) {
			return 0;
		}

		$rules = json_decode( $json, true );

		if ( ! is_array( $rules ) ) {
			return 0;
		}

		$needle = ArchivePage::QUERY_VAR . '=';
		$count  = 0;

		foreach ( $rules as $target ) {
			if ( is_string( $target ) && false !== strpos( $target, $needle ) ) {
				++$count;
			}
		}

		return $count;
	}
}
