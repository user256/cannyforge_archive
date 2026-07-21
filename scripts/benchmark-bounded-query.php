<?php
/**
 * Ticket 608 benchmark substitute: query-building-logic timing/scaling
 * comparison for the bounded-query fix, standing in for a full page-render
 * benchmark on a live, seeded (>= 20k post) WordPress install.
 *
 * Why a substitute: this sandbox has one live WordPress instance
 * (/var/www/html) shared across several concurrently-running sibling agents
 * (each working in its own git worktree on a different ticket). Seeding it
 * with >= 20k posts via scripts/seed-historic-content.sh --count 20000, or
 * installing this in-progress branch's build into it, would mutate a shared
 * resource none of those other agents expect to change mid-task. There was
 * no ticket-isolated WordPress instance available to render real archive/
 * search pages against, so no page-render or live HTTP cold/warm search
 * timings are reported here — reporting any would mean fabricating numbers
 * against a site this change was never actually run on.
 *
 * What this measures instead: the concrete, removed cost. The old
 * `ContentIndexProvider::count()` ran `WP_Query` with `fields => 'ids'` and
 * `posts_per_page => -1` — MySQL returns every matching post ID, and PHP
 * receives/builds an array sized to the full match count (O(n) in the number
 * of matches). The new `build_count_args()` always requests exactly one row
 * (`posts_per_page => 1`), reading the total from `$wp_query->found_posts`
 * instead (O(1) materialisation regardless of match count). This script
 * simulates just the PHP-side array materialisation both paths did/do with
 * their respective row counts — no WordPress or database dependency, so it
 * is standalone and reproducible anywhere PHP runs. It does not, and cannot,
 * simulate the MySQL-side query cost difference; see the ticket's decisions
 * log for that caveat.
 *
 * Usage: php scripts/benchmark-bounded-query.php
 */

declare(strict_types=1);

/**
 * Simulate the old unbounded path: MySQL returns $matching_posts row IDs,
 * and PHP builds an array holding all of them.
 *
 * @param int $matching_posts Number of matching posts to simulate.
 * @return int[]
 */
function simulate_unbounded_materialisation( int $matching_posts ): array {
	$ids = array();
	for ( $i = 0; $i < $matching_posts; $i++ ) {
		$ids[] = $i + 1;
	}

	return $ids;
}

/**
 * Simulate the new bounded path: exactly one row is ever materialised,
 * regardless of how many posts actually match.
 *
 * @param int $matching_posts Number of matching posts to simulate (only used
 *                            to decide whether any row exists at all).
 * @return int[]
 */
function simulate_bounded_materialisation( int $matching_posts ): array {
	return $matching_posts > 0 ? array( 1 ) : array();
}

/**
 * Time and memory-profile a callable, returning both plus its result.
 *
 * @param callable $run The work to profile.
 * @return array{ms: float, bytes: int, result: mixed}
 */
function profile( callable $run ): array {
	$memory_before = memory_get_usage();
	$start         = hrtime( true );
	$result        = $run();
	$elapsed_ms    = ( hrtime( true ) - $start ) / 1e6;
	$bytes         = memory_get_usage() - $memory_before;

	return array(
		'ms'     => $elapsed_ms,
		'bytes'  => $bytes,
		'result' => $result,
	);
}

$scales = array( 1000, 10000, 20000, 50000, 100000 );

printf( "%10s | %16s %12s | %16s %12s | %10s\n", 'Matches', 'Unbounded ms', 'Unbounded KB', 'Bounded ms', 'Bounded KB', 'Speedup' );
echo str_repeat( '-', 90 ) . "\n";

foreach ( $scales as $n ) {
	$unbounded = profile( static fn () => simulate_unbounded_materialisation( $n ) );
	$bounded   = profile( static fn () => simulate_bounded_materialisation( $n ) );

	$speedup = $bounded['ms'] > 0 ? $unbounded['ms'] / $bounded['ms'] : INF;

	printf(
		"%10d | %16.4f %12.1f | %16.4f %12.1f | %8.1fx\n",
		$n,
		$unbounded['ms'],
		$unbounded['bytes'] / 1024,
		$bounded['ms'],
		$bounded['bytes'] / 1024,
		$speedup
	);
}
