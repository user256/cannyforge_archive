<?php
/**
 * Shared fixture/helpers for the archive search endpoint test suite.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Cache\SearchResultCache;
use CannyForge\Archive\Core\RateLimit\SearchThrottle;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchiveSearchEndpoint;
use CannyForge\Archive\Tests\AjaxResponseSpy;
use CannyForge\Archive\Tests\FakeContentIndexProvider;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * The endpoint's behaviour is split across {@see ArchiveSearchEndpointTest}
 * (registration, nonce verification, payload shape, hostile input — ticket
 * 601), {@see ArchiveSearchEndpointCachingTest}, and {@see
 * ArchiveSearchEndpointThrottleTest} (both ticket 608) to stay under this
 * codebase's PHPMD class-length budget (extract, don't relax the gate — the
 * same call ticket 602 made for `GoogleConnectionControllerTestCase`). This
 * abstract base holds the fixture reset and endpoint-construction helpers
 * they all share.
 */
abstract class ArchiveSearchEndpointTestCase extends TestCase {
	/**
	 * Reset shared in-memory WordPress state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		OptionStore::reset();
		TransientStore::reset();
		AjaxResponseSpy::reset();
		unset( $GLOBALS['cannyforge_test_ajax_referer_valid'] );
		unset( $_SERVER['REMOTE_ADDR'] );
		$_REQUEST = array();
	}

	/**
	 * Clean up superglobals so tests never leak into each other.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_REQUEST = array();
		unset( $GLOBALS['cannyforge_test_ajax_referer_valid'] );
		unset( $_SERVER['REMOTE_ADDR'] );
		parent::tearDown();
	}

	/**
	 * Build an endpoint wired to the given fake index provider, plus optional
	 * explicit cache/throttle instances (ticket 608 tests inject a shared
	 * instance across multiple `handle()` calls to observe cache/throttle
	 * state persisting across requests; every other test lets both default to
	 * a fresh instance backed by the per-test-reset transient store).
	 *
	 * @param FakeContentIndexProvider $index    The fake whole-database query provider.
	 * @param SearchResultCache|null   $cache    Response cache.
	 * @param SearchThrottle|null      $throttle Per-IP abuse-ceiling throttle.
	 * @return ArchiveSearchEndpoint
	 */
	protected function endpoint( FakeContentIndexProvider $index, ?SearchResultCache $cache = null, ?SearchThrottle $throttle = null ): ArchiveSearchEndpoint {
		return new ArchiveSearchEndpoint(
			new OptionsSettingsRepository(),
			$index,
			new ArchiveRenderer(),
			$cache,
			$throttle
		);
	}

	/**
	 * Run the endpoint with the given request fields (plus a valid nonce) and
	 * return the `ContentQuery` the fake provider recorded.
	 *
	 * @param array<string, string> $request Request fields beyond the nonce.
	 * @return ContentQuery
	 */
	protected function run_and_capture_query( array $request ): ContentQuery {
		$GLOBALS['cannyforge_test_ajax_referer_valid'] = true;
		$_REQUEST                                      = array_merge( array( 'nonce' => 'valid-nonce' ), $request );

		$index = new FakeContentIndexProvider();
		$this->endpoint( $index )->handle();

		$query = $index->last_query();
		$this->assertNotNull( $query, 'The provider was never called.' );

		return $query;
	}
}
