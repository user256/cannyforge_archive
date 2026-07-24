<?php
/**
 * Tests for shared content-selection term matching.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\ContentSelection;
use CannyForge\Archive\Core\Archive\FullArchiveQueryArgsBuilder;
use CannyForge\Archive\Core\Archive\TermLabelMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Page-one and continuation must agree on label equality (ticket 730).
 */
final class TermLabelMatcherTest extends TestCase {
	public function test_intersects_is_case_insensitive_and_trimmed(): void {
		$this->assertTrue( TermLabelMatcher::intersects( array( ' News ' ), array( 'news' ) ) );
		$this->assertFalse( TermLabelMatcher::intersects( array( 'News & Events' ), array( 'news-events' ) ) );
	}

	/**
	 * Continuation tax_query terms are deduped by the same normaliser keys.
	 *
	 * @return void
	 */
	public function test_query_builder_dedupes_case_variants_to_one_name_term(): void {
		$args = ( new FullArchiveQueryArgsBuilder() )->page(
			new ContentSelection( array( 'News', 'news', ' News ' ) ),
			array(),
			1
		);

		$this->assertSame( array( 'News' ), $args['tax_query'][0]['terms'] );
	}
}
