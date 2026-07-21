<?php
/**
 * Detects whether a supported third-party SEO plugin is active.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports which {@see SeoProvider} (if any) owns the request's SEO tags.
 *
 * Capability-gated like {@see \CannyForge\Archive\Core\Archive\JetpackStatsSource}:
 * detection is behind injected callables, defaulting to the public
 * `defined()` / `class_exists()` signatures each plugin exposes, so the
 * decision stays unit-testable without either plugin installed. Both
 * providers are checked at every call (not cached) since callers may run
 * before {@see \CannyForge\Archive\Frontend\SeoHead::register()} in tests;
 * within a real WordPress request neither signal changes mid-request, so this
 * is not a hot path concern.
 *
 * Detection signals (best effort, based on each plugin's public documentation
 * as of implementation; not yet verified against a live install — see the
 * real-WordPress gap noted in ticket 615's PR):
 * - Yoast SEO defines the `WPSEO_VERSION` constant on load.
 * - Rank Math defines the `RANK_MATH_VERSION` constant, and exposes its
 *   facade as `RankMath\RankMath`.
 *
 * When both are somehow active at once, Yoast wins (checked first) — an
 * arbitrary but documented tie-break; supporting two SEO plugins
 * simultaneously is already an unsupported WordPress configuration.
 */
final class SeoProviderDetector {
	/**
	 * Reports whether Yoast SEO is active: fn(): bool.
	 *
	 * @var callable
	 */
	private $yoast_active;

	/**
	 * Reports whether Rank Math is active: fn(): bool.
	 *
	 * @var callable
	 */
	private $rank_math_active;

	/**
	 * Construct the detector.
	 *
	 * @param callable|null $yoast_active     fn(): bool. Defaults to the real Yoast signal.
	 * @param callable|null $rank_math_active fn(): bool. Defaults to the real Rank Math signal.
	 */
	public function __construct( ?callable $yoast_active = null, ?callable $rank_math_active = null ) {
		$this->yoast_active     = $yoast_active ?? static function (): bool {
			return defined( 'WPSEO_VERSION' );
		};
		$this->rank_math_active = $rank_math_active ?? static function (): bool {
			return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath\\RankMath', false );
		};
	}

	/**
	 * The active supported provider, or {@see SeoProvider::None}.
	 *
	 * @return SeoProvider
	 */
	public function detect(): SeoProvider {
		if ( (bool) ( $this->yoast_active )() ) {
			return SeoProvider::Yoast;
		}

		if ( (bool) ( $this->rank_math_active )() ) {
			return SeoProvider::RankMath;
		}

		return SeoProvider::None;
	}
}
