<?php
/**
 * Tests for the options-backed settings repository.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Settings;

use CannyForge\Archive\Contracts\Settings\Mode;
use CannyForge\Archive\Contracts\Settings\PaginationStyle;
use CannyForge\Archive\Core\Settings\OptionsSettingsRepository;
use CannyForge\Archive\Contracts\Settings\Settings;
use CannyForge\Archive\Tests\OptionStore;
use PHPUnit\Framework\TestCase;

/**
 * Round-trip through the WordPress options shim.
 */
class OptionsSettingsRepositoryTest extends TestCase {
	/**
	 * Reset the in-memory option store before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
	}

	/**
	 * With nothing stored, the repository returns brief defaults.
	 *
	 * @return void
	 */
	public function test_get_returns_defaults_when_unset(): void {
		$settings = ( new OptionsSettingsRepository() )->get();

		$this->assertSame( Mode::Blog, $settings->mode() );
		$this->assertSame( 1, $settings->pagination_limit() );
	}

	/**
	 * A saved snapshot loads back identically.
	 *
	 * @return void
	 */
	public function test_save_then_get_round_trips(): void {
		$repository = new OptionsSettingsRepository();
		$settings   = Settings::from_array(
			array(
				'mode'             => 'news',
				'pagination_limit' => 3,
				'pagination_style' => 'leading_tail',
			)
		);

		$repository->save( $settings );
		$loaded = $repository->get();

		$this->assertSame( Mode::News, $loaded->mode() );
		$this->assertSame( 3, $loaded->pagination_limit() );
		$this->assertSame( PaginationStyle::LeadingWithTail, $loaded->pagination_style() );
		$this->assertEquals( $settings->to_array(), $loaded->to_array() );
	}

	/**
	 * A corrupt (non-array) stored value degrades to defaults rather than fatals.
	 *
	 * @return void
	 */
	public function test_get_tolerates_corrupt_stored_value(): void {
		OptionStore::set( OptionsSettingsRepository::OPTION_KEY, 'corrupt' );

		$settings = ( new OptionsSettingsRepository() )->get();

		$this->assertSame( Mode::Blog, $settings->mode() );
	}
}
