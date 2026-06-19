<?php
/**
 * Minimal shim for the WordPress escaping / i18n / form helpers the admin view
 * touches, so the renderer can be exercised without a WordPress runtime.
 *
 * These are deliberately faithful-but-minimal: escapers behave like their real
 * counterparts closely enough for output assertions, i18n is pass-through, and
 * form helpers emit representative markup. Each is guarded so a real WordPress
 * environment takes precedence.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! function_exists( '__' ) ) {
	/**
	 * Pass-through translation.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain (ignored).
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Translate then HTML-escape.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain (ignored).
	 * @return string
	 */
	function esc_html__( string $text, string $domain = 'default' ): string {
		unset( $domain );
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * Translate then attribute-escape.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain (ignored).
	 * @return string
	 */
	function esc_attr__( string $text, string $domain = 'default' ): string {
		unset( $domain );
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * HTML-escape.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Attribute-escape.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	/**
	 * Textarea-escape.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_textarea( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * URL-escape (display).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( string $url ): string {
		return htmlspecialchars( $url, ENT_QUOTES );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Non-negative integer cast.
	 *
	 * @param mixed $value Value to cast.
	 * @return int
	 */
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Parse a URL, optionally returning a single component (like PHP's parse_url).
	 *
	 * @param string $url       URL to parse.
	 * @param int    $component Component constant, or -1 for the full array.
	 * @return mixed
	 */
	function wp_parse_url( string $url, int $component = -1 ) {
		return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * URL sanitise (storage/redirect).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url_raw( string $url ): string {
		return $url;
	}
}

if ( ! function_exists( 'checked' ) ) {
	/**
	 * Emit the checked attribute when values match.
	 *
	 * @param mixed $checked Value to compare.
	 * @param mixed $current Value to compare against.
	 * @param bool  $display Whether to echo (true) or return.
	 * @return string
	 */
	function checked( $checked, $current = true, bool $display = true ): string {
		$result = ( $checked === $current ) ? ' checked="checked"' : '';
		if ( $display ) {
			echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		return $result;
	}
}

if ( ! function_exists( 'selected' ) ) {
	/**
	 * Emit the selected attribute when values match.
	 *
	 * @param mixed $selected Value to compare.
	 * @param mixed $current  Value to compare against.
	 * @param bool  $display  Whether to echo (true) or return.
	 * @return string
	 */
	function selected( $selected, $current = true, bool $display = true ): string {
		$result = ( $selected === $current ) ? ' selected="selected"' : '';
		if ( $display ) {
			echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		return $result;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	/**
	 * Emit a representative nonce field.
	 *
	 * @param string $action Nonce action.
	 * @param string $name   Field name.
	 * @return string
	 */
	function wp_nonce_field( string $action = '-1', string $name = '_wpnonce' ): string {
		$field = sprintf(
			'<input type="hidden" name="%s" value="test-nonce-%s">',
			htmlspecialchars( $name, ENT_QUOTES ),
			htmlspecialchars( $action, ENT_QUOTES )
		);
		echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return $field;
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	/**
	 * Emit a representative submit button.
	 *
	 * @param string $text Button label.
	 * @return void
	 */
	function submit_button( string $text = 'Save Changes' ): void {
		$button = sprintf(
			'<button type="submit" class="button button-primary">%s</button>',
			htmlspecialchars( $text, ENT_QUOTES )
		);
		echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
