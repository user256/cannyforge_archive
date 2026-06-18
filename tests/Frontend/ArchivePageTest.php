<?php
/**
 * Tests for the front-end archive page wiring.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Frontend;

use CannyForge\Archive\Core\Archive\ArchiveRenderer;
use CannyForge\Archive\Core\Archive\FixtureEntryProvider;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Frontend\ArchivePage;
use CannyForge\Archive\Tests\HookSpy;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * The archive page registers its endpoint and render hook.
 */
class ArchivePageTest extends TestCase {
	/**
	 * Reset shared state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		HookSpy::reset();
		OptionStore::reset();
	}

	/**
	 * Build a page with default collaborators and the given slug.
	 *
	 * @param string $slug Endpoint slug.
	 * @return ArchivePage
	 */
	private function page( string $slug = ArchivePage::DEFAULT_SLUG ): ArchivePage {
		return new ArchivePage(
			new OptionsSettingsRepository(),
			new FixtureEntryProvider(),
			new ArchiveRenderer(),
			$slug
		);
	}

	/**
	 * Registration wires the init + template_redirect hooks.
	 *
	 * @return void
	 */
	public function test_register_wires_hooks(): void {
		$this->page()->register();

		$this->assertTrue( HookSpy::has( 'init' ) );
		$this->assertTrue( HookSpy::has( 'template_redirect' ) );
	}

	/**
	 * The rewrite endpoint registers under the configured slug.
	 *
	 * @return void
	 */
	public function test_endpoint_registers_under_slug(): void {
		$this->page( 'sitemap' )->add_rewrite_endpoint();

		$this->assertTrue( HookSpy::has( 'endpoint:sitemap' ) );
	}

	/**
	 * An empty slug falls back to the default.
	 *
	 * @return void
	 */
	public function test_empty_slug_falls_back_to_default(): void {
		$this->page( '' )->add_rewrite_endpoint();

		$this->assertTrue( HookSpy::has( 'endpoint:' . ArchivePage::DEFAULT_SLUG ) );
	}
}
