<?php
/**
 * Tests for the settings admin page: menu registration, capability gating,
 * and the save round-trip.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Admin;

use CannyForge\Archive\Admin\SettingsFormParser;
use CannyForge\Archive\Admin\SettingsPage;
use CannyForge\Archive\Admin\SettingsView;
use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use CannyForge\Archive\Tests\TransientStore;
use CannyForge\Archive\Tests\WpDieException;
use PHPUnit\Framework\TestCase;

/**
 * Menu registration, the capability gate on viewing/saving, and the
 * posted-form → parser → repository save round-trip.
 */
class SettingsPageTest extends TestCase {
	/**
	 * Reset in-memory WordPress state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		OptionStore::reset();
		TransientStore::reset();
		$_POST = array();
		unset( $GLOBALS['cannyforge_test_current_user_can'] );
	}

	/**
	 * Clean up superglobals so tests never leak into each other.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * `register()` wires the page's render callback to `admin_menu`.
	 *
	 * @return void
	 */
	public function test_register_wires_admin_menu_hook(): void {
		$page = $this->page();
		$page->register();

		$this->assertTrue( HookSpy::has( 'admin_menu' ) );
		$this->assertSame( array( $page, 'add_menu_page' ), HookSpy::first( 'admin_menu' ) );
	}

	/**
	 * `add_menu_page()` registers the page under the settings capability and
	 * the plugin's slug.
	 *
	 * @return void
	 */
	public function test_add_menu_page_registers_under_slug_and_capability(): void {
		$this->page()->add_menu_page();

		$this->assertTrue( HookSpy::has( 'menu:' . SettingsPage::PAGE_SLUG ) );
	}

	/**
	 * Viewing the settings page is refused for a user lacking the required
	 * capability, before any save is attempted.
	 *
	 * @return void
	 */
	public function test_render_page_refused_without_capability(): void {
		$repository                                  = new OptionsSettingsRepository();
		$page                                        = $this->page( $repository );
		$GLOBALS['cannyforge_test_current_user_can'] = false;

		$_POST = $this->valid_post_payload();

		$this->expectException( WpDieException::class );

		try {
			$page->render_page();
		} finally {
			// The capability gate runs before maybe_save(): nothing was persisted.
			$this->assertSame( Mode::Blog, $repository->get()->mode() );
		}
	}

	/**
	 * With no posted nonce field (a plain page view, not a submission), the
	 * page renders the current settings and does not save anything.
	 *
	 * @return void
	 */
	public function test_render_page_without_post_data_renders_without_saving(): void {
		$repository = new OptionsSettingsRepository();
		$page       = $this->page( $repository );

		ob_start();
		$page->render_page();
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'Settings saved.', $html );
		$this->assertSame( Mode::Blog, $repository->get()->mode() );
	}

	/**
	 * A posted form with a valid nonce and the required capability round-trips
	 * through the parser into the repository, and a success notice is shown.
	 *
	 * @return void
	 */
	public function test_save_round_trip_persists_posted_settings_via_repository(): void {
		$repository = new OptionsSettingsRepository();
		$page       = $this->page( $repository );

		$_POST = $this->valid_post_payload(
			array(
				'mode'             => 'news',
				'pagination_limit' => '7',
			)
		);

		ob_start();
		$page->render_page();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Settings saved.', $html );

		$saved = $repository->get();
		$this->assertSame( Mode::News, $saved->mode() );
		$this->assertSame( 7, $saved->pagination_limit() );
	}

	/**
	 * A posted form with an invalid nonce is rejected: nothing is persisted
	 * and no success notice is shown, but the page still renders (no die).
	 *
	 * @return void
	 */
	public function test_save_round_trip_refused_without_valid_nonce(): void {
		$repository = new OptionsSettingsRepository();
		$page       = $this->page( $repository );

		$_POST                              = $this->valid_post_payload( array( 'mode' => 'news' ) );
		$_POST[ SettingsView::NONCE_FIELD ] = 'not-a-real-nonce';

		ob_start();
		$page->render_page();
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'Settings saved.', $html );
		$this->assertSame( Mode::Blog, $repository->get()->mode() );
	}

	// -- Helpers -----------------------------------------------------------------

	/**
	 * Build a settings page wired to real, in-memory collaborators.
	 *
	 * @param OptionsSettingsRepository|null $repository Settings repository.
	 * @return SettingsPage
	 */
	private function page( ?OptionsSettingsRepository $repository = null ): SettingsPage {
		return new SettingsPage(
			$repository ?? new OptionsSettingsRepository(),
			new SettingsFormParser(),
			new SettingsView()
		);
	}

	/**
	 * A valid posted-form payload: a correct nonce plus a default mode, with
	 * any overrides merged in.
	 *
	 * @param array<string, mixed> $overrides Fields to override/add.
	 * @return array<string, mixed>
	 */
	private function valid_post_payload( array $overrides = array() ): array {
		return array_merge(
			array(
				SettingsView::NONCE_FIELD => 'test-nonce-' . SettingsView::NONCE_ACTION,
				'mode'                    => 'blog',
			),
			$overrides
		);
	}
}
