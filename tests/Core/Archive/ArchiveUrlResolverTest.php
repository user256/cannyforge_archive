<?php
/**
 * Tests for the archive URL resolver.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\ArchiveUrlResolver;
use PHPUnit\Framework\TestCase;

/**
 * The resolver is the single source of truth for the archive endpoint URL and
 * the destination derived from it (ticket 612).
 */
class ArchiveUrlResolverTest extends TestCase {
	/**
	 * The endpoint URL is built from the configured slug under the site root.
	 *
	 * @return void
	 */
	public function test_endpoint_url_uses_configured_slug(): void {
		$resolver = new ArchiveUrlResolver( 'stories' );

		$this->assertSame( 'http://example.test/stories/', $resolver->endpoint_url() );
	}

	/**
	 * An empty slug falls back to the default.
	 *
	 * @return void
	 */
	public function test_empty_slug_falls_back_to_default(): void {
		$resolver = new ArchiveUrlResolver( '' );

		$this->assertSame( 'http://example.test/' . ArchiveUrlResolver::DEFAULT_SLUG . '/', $resolver->endpoint_url() );
	}

	/**
	 * With no `archive_url` override, the destination is the endpoint URL —
	 * never empty, unlike the pre-612 behaviour that redirected straight to
	 * the (possibly unset) `archive_url` setting.
	 *
	 * @return void
	 */
	public function test_destination_falls_back_to_endpoint_url_when_unconfigured(): void {
		$resolver = new ArchiveUrlResolver();

		$this->assertSame(
			'http://example.test/archive/',
			$resolver->destination_url( new Settings() )
		);
	}

	/**
	 * A configured `archive_url` override takes precedence over the endpoint.
	 *
	 * @return void
	 */
	public function test_destination_honours_configured_override(): void {
		$resolver = new ArchiveUrlResolver();
		$settings = new Settings( archive_url: 'https://elsewhere.test/all/' );

		$this->assertSame( 'https://elsewhere.test/all/', $resolver->destination_url( $settings ) );
	}

	/**
	 * The default constructor uses the default slug.
	 *
	 * @return void
	 */
	public function test_default_constructor_uses_default_slug(): void {
		$resolver = new ArchiveUrlResolver();

		$this->assertSame( 'http://example.test/archive/', $resolver->endpoint_url() );
	}
}
