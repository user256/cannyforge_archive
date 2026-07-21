<?php
/**
 * Test double for the whole-database content index provider.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests;

use CannyForge\Archive\Contracts\Archive\ContentPage;
use CannyForge\Archive\Contracts\Archive\ContentQuery;
use CannyForge\Archive\Core\Archive\ContentIndexProvider;

/**
 * Records the {@see ContentQuery} it is asked to run and returns a canned
 * {@see ContentPage}, instead of running a real `WP_Query` (which does not
 * exist in the shim-only unit-test runtime).
 *
 * Overriding `provide()` — rather than mocking the class — is what ticket 601
 * needed {@see ContentIndexProvider} to stop being `final` for: it lets
 * `ArchiveSearchEndpointTest` assert on the query the endpoint builds from
 * `$_REQUEST`, without ever touching `WP_Query` internals.
 */
final class FakeContentIndexProvider extends ContentIndexProvider {
	/**
	 * The query passed to the most recent {@see self::provide()} call, or null
	 * if it has not been called.
	 *
	 * @var ContentQuery|null
	 */
	private ?ContentQuery $last_query = null;

	/**
	 * How many times {@see self::provide()} has been called.
	 *
	 * @var int
	 */
	private int $call_count = 0;

	/**
	 * The canned page to return from {@see self::provide()}.
	 *
	 * @var ContentPage
	 */
	private ContentPage $page;

	/**
	 * Construct the fake.
	 *
	 * @param ContentPage|null $page The page to return; defaults to an empty result.
	 */
	public function __construct( ?ContentPage $page = null ) {
		$this->page = $page ?? new ContentPage( array(), 0, 1, 20 );
	}

	/**
	 * Record the query and return the canned page.
	 *
	 * @param ContentQuery $query The request.
	 * @return ContentPage
	 */
	public function provide( ContentQuery $query ): ContentPage {
		$this->last_query = $query;
		++$this->call_count;

		return $this->page;
	}

	/**
	 * The most recent query passed to {@see self::provide()}, or null when it
	 * has never been called.
	 *
	 * @return ContentQuery|null
	 */
	public function last_query(): ?ContentQuery {
		return $this->last_query;
	}

	/**
	 * How many times {@see self::provide()} has been called.
	 *
	 * @return int
	 */
	public function call_count(): int {
		return $this->call_count;
	}
}
