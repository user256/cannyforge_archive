<?php
/**
 * Decides whether the pagination replacement applies to a request.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Pagination;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Contracts\Settings\Targeting;

/**
 * Maps the current archive-type context and the targeting toggles to a single
 * yes/no: does the pagination replacement (ticket 107) apply here?
 *
 * Pure and framework-free so ticket 107 has one testable decision point. A
 * request that is none of the four known archive types is never targeted.
 */
final class TargetingPredicate {
	/**
	 * Whether the replacement applies to the given request context.
	 *
	 * @param Targeting      $targeting The configured targeting toggles.
	 * @param ArchiveContext $context   The current request's archive-type flags.
	 * @return bool
	 */
	public function applies( Targeting $targeting, ArchiveContext $context ): bool {
		return ( $context->is_category() && $targeting->category() )
			|| ( $context->is_tag() && $targeting->tag() )
			|| ( $context->is_author() && $targeting->author() )
			|| ( $context->is_date() && $targeting->date() );
	}
}
