<?php
/**
 * Tests for the shared canonical URL resolver.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Seo;

use CannyForge\Archive\Contracts\Settings\Seo;
use CannyForge\Archive\Core\Seo\CanonicalUrlResolver;
use PHPUnit\Framework\TestCase;

/**
 * The archive has exactly one canonical URL: the SEO override when set, else
 * the endpoint's own URL — never a pagination-link destination.
 */
class CanonicalUrlResolverTest extends TestCase {
	/**
	 * With no override, the endpoint's own URL is the canonical.
	 *
	 * @return void
	 */
	public function test_falls_back_to_endpoint_url(): void {
		$resolver = new CanonicalUrlResolver();

		$this->assertSame(
			'https://site.test/archive/',
			$resolver->resolve( new Seo(), 'https://site.test/archive/' )
		);
	}

	/**
	 * A configured canonical override always wins.
	 *
	 * @return void
	 */
	public function test_override_takes_precedence(): void {
		$resolver = new CanonicalUrlResolver();
		$seo      = new Seo( '', '', true, true, 'https://site.test/all-stories/' );

		$this->assertSame(
			'https://site.test/all-stories/',
			$resolver->resolve( $seo, 'https://site.test/archive/' )
		);
	}
}
