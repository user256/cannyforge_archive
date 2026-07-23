<?php
/**
 * Tests for the stepped Google setup wizard screen.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\GoogleWizardPage;
use CannyForge\Archive\Integration\Google\GoogleSettings;
use CannyForge\Archive\Integration\Google\GoogleSettingsStore;
use CannyForge\Archive\Integration\Google\GoogleTokenStore;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Step routing and gating, the state-derived default step, the wizard-save
 * overlay merge, and the return-step helpers the admin-post handlers use.
 */
class GoogleWizardPageTest extends TestCase {
	/**
	 * Reset in-memory WordPress state and superglobals before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
		TransientStore::reset();
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		unset( $GLOBALS['cannyforge_test_current_user_can'] );
	}

	/**
	 * Clean up superglobals so tests never leak into each other.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * The wizard URL addresses the settings page with the wizard flag, and an
	 * explicit step when one is given; an unknown step is dropped.
	 *
	 * @return void
	 */
	public function test_url_addresses_the_settings_page_wizard(): void {
		$this->assertStringContainsString( 'page=cannyforge-archive', GoogleWizardPage::url() );
		$this->assertStringContainsString( 'cf_wizard=google', GoogleWizardPage::url() );
		$this->assertStringContainsString( 'cf_step=connect', GoogleWizardPage::url( GoogleWizardPage::STEP_CONNECT ) );
		$this->assertStringNotContainsString( 'cf_step', GoogleWizardPage::url( 'not-a-step' ) );
	}

	/**
	 * The wizard is requested only when the flag query arg carries its value.
	 *
	 * @return void
	 */
	public function test_is_requested_reads_the_flag_query_arg(): void {
		$this->assertFalse( GoogleWizardPage::is_requested() );

		$_GET['cf_wizard'] = 'google';
		$this->assertTrue( GoogleWizardPage::is_requested() );

		$_GET['cf_wizard'] = 'other';
		$this->assertFalse( GoogleWizardPage::is_requested() );
	}

	/**
	 * Admin-post handlers redirect back into the wizard only when the posted
	 * form carried a valid wizard step.
	 *
	 * @return void
	 */
	public function test_redirect_base_follows_the_posted_return_step(): void {
		$this->assertSame( 'fallback', GoogleWizardPage::redirect_base_from_request( 'fallback' ) );

		$_POST['cf_wizard_step'] = 'connect';
		$this->assertStringContainsString( 'cf_step=connect', GoogleWizardPage::redirect_base_from_request( 'fallback' ) );

		$_POST['cf_wizard_step'] = 'not-a-step';
		$this->assertSame( 'fallback', GoogleWizardPage::redirect_base_from_request( 'fallback' ) );
	}

	/**
	 * A fresh install lands on the Signal step.
	 *
	 * @return void
	 */
	public function test_fresh_install_defaults_to_the_signal_step(): void {
		$html = $this->render();

		$this->assertStringContainsString( 'Choose your content signal', $html );
		$this->assertStringContainsString( 'Analytics only', $html );
		$this->assertStringContainsString( 'Search Console + GA4 fallback', $html );
		$this->assertStringContainsString( 'aria-current="step"', $html );
	}

	/**
	 * With credentials saved but no connection, the wizard defaults to the
	 * Connect step, and a request for the unreachable Property step falls
	 * back to it as well.
	 *
	 * @return void
	 */
	public function test_defaults_to_connect_once_credentials_are_saved(): void {
		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret' ) );

		$this->assertStringContainsString( 'Connect your Google account', $this->render() );

		$_GET['cf_step'] = 'property';
		$this->assertStringContainsString( 'Connect your Google account', $this->render() );
	}

	/**
	 * With a live connection and a chosen property the wizard lands on the
	 * summary, and the checklist shows every required item green.
	 *
	 * @return void
	 */
	public function test_completed_setup_lands_on_the_summary(): void {
		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ) );
		$tokens = new GoogleTokenStore();
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );
		$tokens->set_analytics_scope_granted( true );

		$html = $this->render();

		$this->assertStringContainsString( 'Setup summary', $html );
		$this->assertStringContainsString( 'Credentials saved', $html );
		$this->assertStringContainsString( 'Google account connected', $html );
		$this->assertStringContainsString( 'Refresh Search Console', $html );
	}

	/**
	 * An explicitly requested, reachable step is honoured over the default.
	 *
	 * @return void
	 */
	public function test_reachable_requested_step_is_honoured(): void {
		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret' ) );

		$_GET['cf_step'] = 'credentials';
		$this->assertStringContainsString( 'Add your credentials', $this->render() );
	}

	/**
	 * The GA4 app instructions name both APIs used by the setup flow.
	 *
	 * @return void
	 */
	public function test_ga4_app_instructions_enable_data_and_admin_apis(): void {
		$_GET = array(
			'cf_step'   => 'app',
			'cf_signal' => 'sc_ga4',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test-only display routing fixture.
		$_REQUEST = $_GET;

		$html = $this->render();

		$this->assertStringContainsString( 'Analytics Data API', $html );
		$this->assertStringContainsString( 'Analytics Admin API', $html );
		$this->assertStringContainsString( 'analyticsdata.googleapis.com', $html );
		$this->assertStringContainsString( 'analyticsadmin.googleapis.com', $html );
	}

	/**
	 * Analytics-only setup shows only Analytics API instructions.
	 *
	 * @return void
	 */
	public function test_analytics_only_app_instructions_omit_search_console_api(): void {
		$_GET = array(
			'cf_step'   => 'app',
			'cf_signal' => 'ga4',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test-only display routing fixture.
		$_REQUEST = $_GET;

		$html = $this->render();

		$this->assertStringContainsString( 'Analytics Data API', $html );
		$this->assertStringContainsString( 'Analytics Admin API', $html );
		$this->assertStringNotContainsString( 'searchconsole.googleapis.com', $html );
	}

	/**
	 * A wizard step save merges over the stored Google settings: fields the
	 * step did not post keep their stored values.
	 *
	 * @return void
	 */
	public function test_wizard_save_overlays_posted_fields_over_stored_settings(): void {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'old-id', 'old-secret', 'sc-domain:example.com', 45, '123' ) );

		$_POST = array(
			GoogleWizardPage::NONCE_FIELD => 'test-nonce-' . GoogleWizardPage::NONCE_ACTION,
			'google_client_id'            => 'new-id',
			'google_client_secret'        => '',
		);

		$this->render();

		$saved = $store->get();
		$this->assertSame( 'new-id', $saved->client_id() );
		$this->assertSame( 'old-secret', $saved->client_secret(), 'A blank posted secret keeps the stored secret.' );
		$this->assertSame( 'sc-domain:example.com', $saved->search_console_site_url(), 'Unposted fields keep their stored values.' );
		$this->assertSame( 45, $saved->report_window_days() );
		$this->assertSame( '123', $saved->ga4_property_id() );
	}

	/**
	 * A wizard save without a valid nonce persists nothing.
	 *
	 * @return void
	 */
	public function test_wizard_save_refused_without_valid_nonce(): void {
		$store = new GoogleSettingsStore();
		$store->save( new GoogleSettings( 'old-id', 'old-secret' ) );

		$_POST = array(
			GoogleWizardPage::NONCE_FIELD => 'not-a-real-nonce',
			'google_client_id'            => 'attacker-id',
		);

		$this->render();

		$this->assertSame( 'old-id', $store->get()->client_id() );
	}

	/**
	 * The GA4 signal moves property selection to the post-connect Property step,
	 * where the account-backed dropdown can be populated.
	 *
	 * @return void
	 */
	public function test_ga4_field_renders_only_for_the_ga4_signal(): void {
		$_GET['cf_step'] = 'credentials';

		$this->assertStringNotContainsString( 'google_ga4_property_id', $this->render() );

		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ) );
		$tokens = new GoogleTokenStore();
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );
		$tokens->set_analytics_scope_granted( true );
		$_GET['cf_step']       = 'property';
		$_REQUEST['cf_signal'] = 'sc_ga4';
		$this->assertStringContainsString( 'name="google_ga4_property_id"', $this->render() );
	}

	/**
	 * Analytics-only has a first-class property path and does not render a
	 * Search Console field.
	 *
	 * @return void
	 */
	public function test_analytics_only_property_path_renders_ga4_as_required_property(): void {
		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret', '', 30, '123' ) );
		$tokens = new GoogleTokenStore();
		$tokens->set_status( GoogleTokenStore::STATUS_CONNECTED );
		$tokens->set_analytics_scope_granted( true );
		$_GET['cf_step']       = 'property';
		$_REQUEST['cf_signal'] = 'ga4';

		$html = $this->render();

		$this->assertStringContainsString( 'Choose your Analytics property', $html );
		$this->assertStringContainsString( 'name="google_ga4_property_id"', $html );
		$this->assertStringNotContainsString( '<select name="google_search_console_site_url"', $html );
	}

	/**
	 * A legacy/SC-only connection cannot expose a working GA4 picker merely
	 * because the request signal was changed to sc_ga4.
	 *
	 * @return void
	 */
	public function test_sc_only_connection_requires_analytics_reconnect_before_ga4_picker(): void {
		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ) );
		( new GoogleTokenStore() )->set_status( GoogleTokenStore::STATUS_CONNECTED );
		$_GET['cf_step']       = 'property';
		$_REQUEST['cf_signal'] = 'sc_ga4';

		$html = $this->render();

		$this->assertStringContainsString( 'Reconnect Google with Analytics access', $html );
		$this->assertStringNotContainsString( 'name="google_ga4_property_id"', $html );
		$this->assertStringNotContainsString( 'action=cannyforge_archive_google_ga4_properties', $html );
	}

	/**
	 * Account-step navigation preserves the selected signal, including the
	 * Connect Back link and checklist links on Finish.
	 *
	 * @return void
	 */
	public function test_account_navigation_preserves_ga4_signal(): void {
		$this->save_settings( new GoogleSettings( 'client-id', 'client-secret', 'sc-domain:example.com' ) );
		( new GoogleTokenStore() )->set_status( GoogleTokenStore::STATUS_CONNECTED );
		$_GET = array(
			'cf_step'   => 'connect',
			'cf_signal' => 'sc_ga4',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test-only display routing fixture.
		$_REQUEST = $_GET;

		$connect_html = $this->render();
		$this->assertStringContainsString( 'Google Analytics 4 (read-only)', $connect_html );
		$this->assertStringContainsString( 'cf_step=credentials&amp;cf_signal=sc_ga4', $connect_html );

		$_GET = array(
			'cf_step'   => 'done',
			'cf_signal' => 'sc_ga4',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test-only display routing fixture.
		$_REQUEST  = $_GET;
		$done_html = $this->render();
		$this->assertStringContainsString( 'cf_step=connect&amp;cf_signal=sc_ga4', $done_html );
		$this->assertStringContainsString( 'cf_step=property&amp;cf_signal=sc_ga4', $done_html );
	}

	/**
	 * Persist Google settings through the store used by the wizard.
	 *
	 * @param GoogleSettings $settings Settings to store.
	 * @return void
	 */
	private function save_settings( GoogleSettings $settings ): void {
		( new GoogleSettingsStore() )->save( $settings );
	}

	/**
	 * Render the wizard page and capture its output.
	 *
	 * @return string
	 */
	private function render(): string {
		ob_start();
		( new GoogleWizardPage() )->render_page( '', 'error' );

		return (string) ob_get_clean();
	}
}
