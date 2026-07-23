<?php
/**
 * Tests for the Google Search Console property selector.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleWizardAccountStepsView;
use CannyForge\Archive\Admin\GoogleWizardPage;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use PHPUnit\Framework\TestCase;

/**
 * The connected account's properties are rendered as selectable options on
 * the wizard's Property step.
 */
final class GooglePropertyDropdownViewTest extends TestCase {
	/**
	 * Connected properties are presented as a selectable list rather than a
	 * manually entered identifier, with a saved-but-unknown property kept
	 * selectable and a refresh action available.
	 *
	 * @return void
	 */
	public function test_renders_search_console_property_dropdown(): void {
		ob_start();
		( new GoogleWizardAccountStepsView() )->property(
			new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ),
			array(
				array(
					'site_url'   => 'https://example.com/',
					'permission' => 'siteOwner',
				),
			)
		);
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<select name="google_search_console_site_url"', $html );
		$this->assertStringContainsString( 'https://example.com/ (siteOwner)', $html );
		$this->assertStringContainsString( 'sc-domain:example.com (saved)', $html );
		$this->assertStringContainsString( 'Load properties', $html );
		$this->assertStringNotContainsString( 'Save property and continue', $html );
		$this->assertStringContainsString( 'Save and finish', $html );
		$this->assertStringContainsString( 'name="google_report_window_days"', $html );
		$this->assertStringContainsString( 'action=cannyforge_archive_google_properties', $html );
	}

	/**
	 * The GA4 signal renders the account-backed GA4 selector beside Search
	 * Console and keeps the refresh action available.
	 *
	 * @return void
	 */
	public function test_renders_ga4_property_dropdown_for_ga4_signal(): void {
		ob_start();
		( new GoogleWizardAccountStepsView() )->property(
			new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ),
			array(),
			array(
				array(
					'property_id'  => '123456789',
					'display_name' => 'Example Analytics',
					'account_name' => 'Example account',
				),
			),
			GoogleWizardPage::SIGNAL_SC_GA4
		);
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="google_ga4_property_id"', $html );
		$this->assertStringContainsString( 'Example Analytics (123456789)', $html );
		$this->assertStringContainsString( 'action=cannyforge_archive_google_ga4_properties', $html );
	}
}
