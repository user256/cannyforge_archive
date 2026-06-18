<?php
/**
 * Tests for the archive HTML renderer.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Archive;

use CannyForge\Archive\Contracts\Archive\ArchiveEntry;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use PHPUnit\Framework\TestCase;

/**
 * The renderer honours the link-type toggles and escapes output.
 */
class ArchiveRendererTest extends TestCase {
	/**
	 * Build a representative fixture entry.
	 *
	 * @return ArchiveEntry
	 */
	private function entry(): ArchiveEntry {
		return new ArchiveEntry(
			'https://example.com/post',
			'A Post Title',
			'A short description.',
			'https://example.com/img.jpg',
			array( 'News' ),
			array( 'world' ),
			'Jane Doe',
			'2026-06-18'
		);
	}

	/**
	 * Render one fixture entry with the given link-type/filter settings.
	 *
	 * @param array<string, mixed> $settings Settings overrides.
	 * @return string
	 */
	private function render( array $settings ): string {
		return ( new ArchiveRenderer() )->render(
			array( $this->entry() ),
			Settings::from_array( $settings )
		);
	}

	/**
	 * Defaults (Title on, Description/Image off): title link only.
	 *
	 * @return void
	 */
	public function test_title_only_by_default(): void {
		$html = $this->render( array() );

		$this->assertStringContainsString( 'href="https://example.com/post"', $html );
		$this->assertStringContainsString( 'A Post Title', $html );
		$this->assertStringNotContainsString( 'A short description.', $html );
		$this->assertStringNotContainsString( '<img', $html );
	}

	/**
	 * Description toggle on adds the description.
	 *
	 * @return void
	 */
	public function test_description_when_enabled(): void {
		$html = $this->render( array( 'link_types' => array( 'description' => true ) ) );

		$this->assertStringContainsString( 'A short description.', $html );
	}

	/**
	 * Featured-image toggle on adds the image.
	 *
	 * @return void
	 */
	public function test_featured_image_when_enabled(): void {
		$html = $this->render( array( 'link_types' => array( 'featured_image' => true ) ) );

		$this->assertStringContainsString( '<img', $html );
		$this->assertStringContainsString( 'src="https://example.com/img.jpg"', $html );
	}

	/**
	 * All link types on render together.
	 *
	 * @return void
	 */
	public function test_all_link_types_combined(): void {
		$html = $this->render(
			array(
				'link_types' => array(
					'title'          => true,
					'description'    => true,
					'featured_image' => true,
				),
			)
		);

		$this->assertStringContainsString( '<img', $html );
		$this->assertStringContainsString( 'A Post Title', $html );
		$this->assertStringContainsString( 'A short description.', $html );
	}

	/**
	 * With the title toggle off, the URL is the link text.
	 *
	 * @return void
	 */
	public function test_title_off_uses_url_as_label(): void {
		$html = $this->render( array( 'link_types' => array( 'title' => false ) ) );

		$this->assertStringNotContainsString( '>A Post Title<', $html );
		$this->assertStringContainsString( '>https://example.com/post<', $html );
	}

	/**
	 * Filter metadata is emitted as data-attributes for the client-side filters.
	 *
	 * @return void
	 */
	public function test_emits_filter_data_attributes(): void {
		$html = $this->render( array() );

		$this->assertStringContainsString( 'data-categories="News"', $html );
		$this->assertStringContainsString( 'data-tags="world"', $html );
		$this->assertStringContainsString( 'data-author="Jane Doe"', $html );
		$this->assertStringContainsString( 'data-month="2026-06"', $html );
	}

	/**
	 * Output is a single accessible nav/list structure.
	 *
	 * @return void
	 */
	public function test_wraps_entries_in_nav_and_list(): void {
		$html = $this->render( array() );

		$this->assertStringContainsString( '<nav class="cannyforge-archive"', $html );
		$this->assertStringContainsString( '<ul class="cannyforge-archive__list">', $html );
		$this->assertStringContainsString( '<li class="cannyforge-archive__item"', $html );
	}

	/**
	 * Markup in entry fields is escaped.
	 *
	 * @return void
	 */
	public function test_escapes_entry_markup(): void {
		$html = ( new ArchiveRenderer() )->render(
			array( new ArchiveEntry( 'https://example.com/x', '<script>alert(1)</script>' ) ),
			new Settings()
		);

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}
}
