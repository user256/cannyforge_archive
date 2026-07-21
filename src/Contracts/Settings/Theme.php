<?php
/**
 * Front-end archive theming settings.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Contracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic style controls for the archive and pagination blocks.
 *
 * Keeps the configurable presentation narrow and predictable: one layout mode
 * plus a few core colours that map cleanly to CSS variables. Invalid values
 * fall back to safe defaults so rendering never breaks on bad stored input.
 */
final class Theme {
	/**
	 * The default layout.
	 */
	public const LAYOUT_DEFAULT = 'default';

	/**
	 * The card-style archive layout.
	 */
	public const LAYOUT_CARDS = 'cards';

	/**
	 * The simpler list-style archive layout.
	 */
	public const LAYOUT_LIST = 'list';

	/**
	 * Accent colour used for links and key actions.
	 */
	private const DEFAULT_ACCENT_COLOR = '#6d4aff';

	/**
	 * Surface/background colour for the archive container and cards.
	 */
	private const DEFAULT_SURFACE_COLOR = '#ffffff';

	/**
	 * Primary text colour.
	 */
	private const DEFAULT_TEXT_COLOR = '#1b143f';

	/**
	 * Border/chip colour.
	 */
	private const DEFAULT_BORDER_COLOR = '#d8dbe8';

	/**
	 * Layout mode.
	 *
	 * @var string
	 */
	private string $layout;

	/**
	 * Accent colour.
	 *
	 * @var string
	 */
	private string $accent_color;

	/**
	 * Surface colour.
	 *
	 * @var string
	 */
	private string $surface_color;

	/**
	 * Text colour.
	 *
	 * @var string
	 */
	private string $text_color;

	/**
	 * Border colour.
	 *
	 * @var string
	 */
	private string $border_color;

	/**
	 * Construct the theme settings.
	 *
	 * @param string $layout        Layout mode.
	 * @param string $accent_color  Accent colour.
	 * @param string $surface_color Surface colour.
	 * @param string $text_color    Text colour.
	 * @param string $border_color  Border colour.
	 */
	public function __construct(
		string $layout = self::LAYOUT_CARDS,
		string $accent_color = self::DEFAULT_ACCENT_COLOR,
		string $surface_color = self::DEFAULT_SURFACE_COLOR,
		string $text_color = self::DEFAULT_TEXT_COLOR,
		string $border_color = self::DEFAULT_BORDER_COLOR
	) {
		$this->layout        = self::sanitise_layout( $layout );
		$this->accent_color  = self::sanitise_colour( $accent_color, self::DEFAULT_ACCENT_COLOR );
		$this->surface_color = self::sanitise_colour( $surface_color, self::DEFAULT_SURFACE_COLOR );
		$this->text_color    = self::sanitise_colour( $text_color, self::DEFAULT_TEXT_COLOR );
		$this->border_color  = self::sanitise_colour( $border_color, self::DEFAULT_BORDER_COLOR );
	}

	/**
	 * The chosen layout mode.
	 *
	 * @return string
	 */
	public function layout(): string {
		return $this->layout;
	}

	/**
	 * The accent colour.
	 *
	 * @return string
	 */
	public function accent_color(): string {
		return $this->accent_color;
	}

	/**
	 * The surface colour.
	 *
	 * @return string
	 */
	public function surface_color(): string {
		return $this->surface_color;
	}

	/**
	 * The text colour.
	 *
	 * @return string
	 */
	public function text_color(): string {
		return $this->text_color;
	}

	/**
	 * The border colour.
	 *
	 * @return string
	 */
	public function border_color(): string {
		return $this->border_color;
	}

	/**
	 * Build from a raw associative array.
	 *
	 * @param array<string, mixed> $data Raw stored data.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			self::string( $data['layout'] ?? null ),
			self::string( $data['accent_color'] ?? null ),
			self::string( $data['surface_color'] ?? null ),
			self::string( $data['text_color'] ?? null ),
			self::string( $data['border_color'] ?? null )
		);
	}

	/**
	 * Export as a plain associative array for persistence.
	 *
	 * @return array{layout: string, accent_color: string, surface_color: string, text_color: string, border_color: string}
	 */
	public function to_array(): array {
		return array(
			'layout'        => $this->layout,
			'accent_color'  => $this->accent_color,
			'surface_color' => $this->surface_color,
			'text_color'    => $this->text_color,
			'border_color'  => $this->border_color,
		);
	}

	/**
	 * Coerce a raw scalar into a trimmed string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function string( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Keep the layout to the supported set.
	 *
	 * @param string $layout Raw layout.
	 * @return string
	 */
	private static function sanitise_layout( string $layout ): string {
		if ( self::LAYOUT_LIST === $layout ) {
			return self::LAYOUT_LIST;
		}
		if ( self::LAYOUT_DEFAULT === $layout ) {
			return self::LAYOUT_DEFAULT;
		}
		return self::LAYOUT_CARDS;
	}

	/**
	 * Accept only short/long hex colours; fall back otherwise.
	 *
	 * @param string $colour   Raw colour.
	 * @param string $fallback Fallback colour.
	 * @return string
	 */
	private static function sanitise_colour( string $colour, string $fallback ): string {
		return 1 === preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $colour ) ? strtolower( $colour ) : $fallback;
	}
}
