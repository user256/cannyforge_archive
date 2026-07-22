<?php
/**
 * Tests for the Google Search Console property selector.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\SettingsView;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use PHPUnit\Framework\TestCase;

/**
 * The connected account's properties are rendered as selectable options.
 */
final class GooglePropertyDropdownViewTest extends TestCase {
	/**
	 * Connected properties are presented as a selectable list rather than a
	 * manually entered identifier.
	 *
	 * @return void
	 */
	public function test_renders_search_console_property_dropdown(): void {
		ob_start();
		( new SettingsView() )->render(
			Settings::from_array( array( 'mode' => 'blog' ) ),
			'admin.php?page=cannyforge-archive',
			'',
			new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ),
			GoogleTokenStore::STATUS_CONNECTED,
			true,
			'',
			'',
			'',
			'',
			array(
				array(
					'site_url'   => 'https://example.com/',
					'permission' => 'siteOwner',
				),
			),
			'admin-post.php?action=cannyforge_archive_google_properties'
		);
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<select name="google_search_console_site_url"', $html );
		$this->assertStringContainsString( 'https://example.com/ (siteOwner)', $html );
		$this->assertStringContainsString( 'sc-domain:example.com (saved)', $html );
		$this->assertStringContainsString( 'Load Search Console properties', $html );
		$this->assertStringContainsString( 'Save property and continue', $html );
		$this->assertStringContainsString( 'Save credentials and continue', $html );
		$this->assertStringContainsString( 'data-cf-google-save-details', $html );
		$this->assertStringContainsString( 'data-cf-google-save-status', $html );
		$this->assertStringContainsString( 'Next: refresh Search Console data', $html );
	}
}
