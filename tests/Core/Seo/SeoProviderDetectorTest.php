<?php
/**
 * Tests for third-party SEO plugin detection.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Tests\Core\Seo;

use CannyForge\Archive\Core\Seo\SeoProvider;
use CannyForge\Archive\Core\Seo\SeoProviderDetector;
use PHPUnit\Framework\TestCase;

/**
 * Detection is injectable (mirrors {@see \CannyForge\Archive\Core\Archive\JetpackStatsSourceTest}),
 * so the presence of Yoast SEO / Rank Math is faked rather than requiring
 * either plugin installed.
 */
class SeoProviderDetectorTest extends TestCase {
	/**
	 * With neither signal present, no provider is detected.
	 *
	 * @return void
	 */
	public function test_detects_none_by_default(): void {
		$detector = new SeoProviderDetector( static fn (): bool => false, static fn (): bool => false );

		$this->assertSame( SeoProvider::None, $detector->detect() );
	}

	/**
	 * The Yoast signal is recognised.
	 *
	 * @return void
	 */
	public function test_detects_yoast(): void {
		$detector = new SeoProviderDetector( static fn (): bool => true, static fn (): bool => false );

		$this->assertSame( SeoProvider::Yoast, $detector->detect() );
	}

	/**
	 * The Rank Math signal is recognised.
	 *
	 * @return void
	 */
	public function test_detects_rank_math(): void {
		$detector = new SeoProviderDetector( static fn (): bool => false, static fn (): bool => true );

		$this->assertSame( SeoProvider::RankMath, $detector->detect() );
	}

	/**
	 * When both signals are present, Yoast wins (documented tie-break).
	 *
	 * @return void
	 */
	public function test_yoast_wins_when_both_present(): void {
		$detector = new SeoProviderDetector( static fn (): bool => true, static fn (): bool => true );

		$this->assertSame( SeoProvider::Yoast, $detector->detect() );
	}

	/**
	 * The default (no-argument) construction reflects the real WordPress
	 * runtime, which has neither plugin loaded in the unit test process.
	 *
	 * @return void
	 */
	public function test_default_signals_report_none_in_test_runtime(): void {
		$detector = new SeoProviderDetector();

		$this->assertSame( SeoProvider::None, $detector->detect() );
	}

	/**
	 * The default (production) Yoast signal is `defined( 'WPSEO_VERSION' )` —
	 * the constant Yoast SEO defines on load. Run in an isolated process so
	 * defining it here cannot leak into other tests in the same run.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_default_yoast_signal_reflects_wpseo_version_constant(): void {
		define( 'WPSEO_VERSION', '99.0' );

		$this->assertSame( SeoProvider::Yoast, ( new SeoProviderDetector() )->detect() );
	}

	/**
	 * The default (production) Rank Math signal is
	 * `defined( 'RANK_MATH_VERSION' )` — the constant Rank Math defines on
	 * load. Run in an isolated process for the same reason as above.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_default_rank_math_signal_reflects_rank_math_version_constant(): void {
		define( 'RANK_MATH_VERSION', '99.0' );

		$this->assertSame( SeoProvider::RankMath, ( new SeoProviderDetector() )->detect() );
	}
}
