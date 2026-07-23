<?php
/**
 * Tests for raw GA4 diagnostic visibility.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\Ga4DiagnosticsView;
use CannyForge\Archive\Integration\Google\Ga4CacheStore;
use PHPUnit\Framework\TestCase;

/**
 * Raw GA4 paths stay visible when one or more paths resolve locally.
 */
final class Ga4DiagnosticsViewTest extends TestCase {
	/**
	 * A local match must not hide the remaining returned GA4 paths.
	 *
	 * @return void
	 */
	public function test_shows_raw_paths_alongside_matched_local_content(): void {
		$cache = new Ga4CacheStore(
			static function ( string $key, $fallback ) {
				unset( $key, $fallback );
				return array(
					'post_ids'    => array( 17 ),
					'source_urls' => array( '/cart/', '/production-only/' ),
				);
			},
			static function ( string $key, $value ): void {
				unset( $key, $value );
			}
		);

		ob_start();
		( new Ga4DiagnosticsView(
			$cache,
			static fn ( int $post_id ): string => 17 === $post_id ? 'Cart' : '',
			static fn ( int $post_id ): string => 17 === $post_id ? 'https://localhost.test/cart/' : ''
		) )->render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Raw GA4 page paths', $html );
		$this->assertStringContainsString( 'GA4 returned 2 page paths; 1 matched', $html );
		$this->assertStringContainsString( '/cart/', $html );
		$this->assertStringContainsString( '/production-only/', $html );
		$this->assertStringContainsString( 'Matched local content', $html );
		$this->assertStringContainsString( 'Cart', $html );
	}
}
