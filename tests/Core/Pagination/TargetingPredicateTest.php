<?php
/**
 * Tests for the archive-type targeting predicate.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Pagination;

use CannyForge\Archive\Contracts\Settings\Targeting;
use CannyForge\Archive\Core\Pagination\ArchiveContext;
use CannyForge\Archive\Core\Pagination\TargetingPredicate;
use PHPUnit\Framework\TestCase;

/**
 * The predicate applies only where the context's type is an enabled target.
 */
class TargetingPredicateTest extends TestCase {
	/**
	 * Context constructors keyed by the archive type they represent.
	 *
	 * @return array<string, callable(): ArchiveContext>
	 */
	private function contexts(): array {
		return array(
			'category' => static fn (): ArchiveContext => new ArchiveContext( true, false, false, false ),
			'tag'      => static fn (): ArchiveContext => new ArchiveContext( false, true, false, false ),
			'author'   => static fn (): ArchiveContext => new ArchiveContext( false, false, true, false ),
			'date'     => static fn (): ArchiveContext => new ArchiveContext( false, false, false, true ),
		);
	}

	/**
	 * With every target enabled, each archive type matches.
	 *
	 * @return void
	 */
	public function test_applies_when_type_enabled(): void {
		$predicate = new TargetingPredicate();
		$all_on    = new Targeting( true, true, true, true );

		foreach ( $this->contexts() as $type => $make ) {
			$this->assertTrue(
				$predicate->applies( $all_on, $make() ),
				"expected {$type} to be targeted when enabled"
			);
		}
	}

	/**
	 * With every target disabled, no archive type matches.
	 *
	 * @return void
	 */
	public function test_does_not_apply_when_type_disabled(): void {
		$predicate = new TargetingPredicate();
		$all_off   = new Targeting( false, false, false, false );

		foreach ( $this->contexts() as $type => $make ) {
			$this->assertFalse(
				$predicate->applies( $all_off, $make() ),
				"expected {$type} not to be targeted when disabled"
			);
		}
	}

	/**
	 * The confirmed defaults: categories and tags on, authors and dates off.
	 *
	 * @return void
	 */
	public function test_default_targeting(): void {
		$predicate = new TargetingPredicate();
		$defaults  = new Targeting();
		$contexts  = $this->contexts();

		$this->assertTrue( $predicate->applies( $defaults, $contexts['category']() ) );
		$this->assertTrue( $predicate->applies( $defaults, $contexts['tag']() ) );
		$this->assertFalse( $predicate->applies( $defaults, $contexts['author']() ) );
		$this->assertFalse( $predicate->applies( $defaults, $contexts['date']() ) );
	}

	/**
	 * A request that is no known archive type is never targeted.
	 *
	 * @return void
	 */
	public function test_non_archive_request_is_never_targeted(): void {
		$predicate = new TargetingPredicate();

		$this->assertFalse(
			$predicate->applies( new Targeting( true, true, true, true ), new ArchiveContext() )
		);
	}
}
