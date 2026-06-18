<?php
/**
 * Tests for the SEO head-tag builder.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Seo;

use CannyForge\Archive\Contracts\Settings\Seo;
use CannyForge\Archive\Core\Seo\HeadTagBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The builder emits the configured robots, title, description, and canonical.
 */
class HeadTagBuilderTest extends TestCase {
	/**
	 * Build the head fragment for the given SEO settings.
	 *
	 * @param Seo    $seo      SEO settings.
	 * @param string $fallback Canonical fallback URL.
	 * @return string
	 */
	private function build( Seo $seo, string $fallback = 'https://site.test/archive/' ): string {
		return ( new HeadTagBuilder() )->build( $seo, $fallback );
	}

	/**
	 * The default robots directive is index, follow.
	 *
	 * @return void
	 */
	public function test_default_robots_is_index_follow(): void {
		$html = $this->build( new Seo() );

		$this->assertStringContainsString( '<meta name="robots" content="index,follow">', $html );
	}

	/**
	 * The noindex / nofollow toggles are reflected in the robots directive.
	 *
	 * @return void
	 */
	public function test_noindex_nofollow_directive(): void {
		$html = $this->build( new Seo( '', '', false, false ) );

		$this->assertStringContainsString( '<meta name="robots" content="noindex,nofollow">', $html );
	}

	/**
	 * A mixed directive (index, nofollow) renders correctly.
	 *
	 * @return void
	 */
	public function test_mixed_directive(): void {
		$html = $this->build( new Seo( '', '', true, false ) );

		$this->assertStringContainsString( '<meta name="robots" content="index,nofollow">', $html );
	}

	/**
	 * Title and meta description render only when set.
	 *
	 * @return void
	 */
	public function test_title_and_description_when_set(): void {
		$html = $this->build( new Seo( 'All Stories', 'Every article we publish.' ) );

		$this->assertStringContainsString( '<title>All Stories</title>', $html );
		$this->assertStringContainsString( '<meta name="description" content="Every article we publish.">', $html );
	}

	/**
	 * Empty title/description are omitted entirely.
	 *
	 * @return void
	 */
	public function test_empty_title_and_description_omitted(): void {
		$html = $this->build( new Seo() );

		$this->assertStringNotContainsString( '<title>', $html );
		$this->assertStringNotContainsString( '<meta name="description"', $html );
	}

	/**
	 * With no canonical override, the fallback (archive) URL is used.
	 *
	 * @return void
	 */
	public function test_canonical_falls_back_to_archive_url(): void {
		$html = $this->build( new Seo() );

		$this->assertStringContainsString( '<link rel="canonical" href="https://site.test/archive/">', $html );
	}

	/**
	 * A configured canonical override takes precedence over the fallback.
	 *
	 * @return void
	 */
	public function test_canonical_override_used(): void {
		$html = $this->build( new Seo( '', '', true, true, 'https://site.test/canonical/' ) );

		$this->assertStringContainsString( '<link rel="canonical" href="https://site.test/canonical/">', $html );
		$this->assertStringNotContainsString( 'archive/', $html );
	}

	/**
	 * Values are escaped.
	 *
	 * @return void
	 */
	public function test_escapes_values(): void {
		$html = $this->build( new Seo( '<script>x</script>', '"quote"' ) );

		$this->assertStringNotContainsString( '<script>x</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}
}
