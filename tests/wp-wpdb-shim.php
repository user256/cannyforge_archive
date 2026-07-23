<?php
/**
 * Minimal in-memory stand-in for WordPress's `$wpdb` global, scoped to what
 * uninstall.php's direct-query Google dynamic transient cleanup needs
 * (ticket 606): `prepare()`, `query()`, `esc_like()`, and the `$options`
 * table-name property. Guarded so a real WordPress environment takes
 * precedence.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

if ( ! class_exists( 'wpdb' ) ) {
	// phpcs:disable PEAR.NamingConventions.ValidClassName.StartWithCapital -- must match the real WordPress `wpdb` class name exactly so `instanceof \wpdb` checks in uninstall.php work against this stand-in.
	/**
	 * Records every query passed to it so tests can assert on the generated
	 * SQL/LIKE pattern without a real database connection.
	 */
	class wpdb {
		// phpcs:enable PEAR.NamingConventions.ValidClassName.StartWithCapital
		/**
		 * Options table name (mirrors the real wpdb property).
		 *
		 * @var string
		 */
		public string $options = 'wp_options';

		/**
		 * Every query passed to {@see self::query()}, in order.
		 *
		 * @var list<string>
		 */
		public array $queries = array();

		/**
		 * Minimal stand-in for `$wpdb->prepare()`: substitutes `%i` and `%s`
		 * placeholders in call order. Sufficient for the single query shape
		 * uninstall.php builds; not a general SQL-escaping implementation.
		 *
		 * @param string $query Query with `%s` placeholders.
		 * @param mixed  ...$args Values to substitute, in order.
		 * @return string
		 */
		public function prepare( string $query, ...$args ): string {
			foreach ( $args as $value ) {
				$identifier_pos = strpos( $query, '%i' );
				$string_pos     = strpos( $query, '%s' );
				if ( false === $identifier_pos && false === $string_pos ) {
					break;
				}

				if ( false !== $identifier_pos && ( false === $string_pos || $identifier_pos < $string_pos ) ) {
					$identifier = str_replace( '`', '``', (string) $value );
					$query      = substr_replace( $query, '`' . $identifier . '`', $identifier_pos, 2 );
				} else {
					$query = substr_replace( $query, "'" . addslashes( (string) $value ) . "'", $string_pos, 2 );
				}
			}

			return $query;
		}

		/**
		 * Record a query instead of executing it against a real database.
		 *
		 * @param string $query SQL query.
		 * @return true
		 */
		public function query( string $query ) {
			$this->queries[] = $query;

			// Simulate the rows removed by uninstall.php's prepared LIKE query.
			// The real wpdb executes this SQL; the test shim mirrors its effect on
			// the in-memory transient store so tests can assert rows are gone.
			$normalised = str_replace( '\\', '', $query );
			preg_match_all( "/_transient(?:_timeout)?_([^']+)%'/", $normalised, $matches );
			foreach ( $matches[1] as $prefix ) {
				\CannyForge\Archive\Tests\TransientStore::delete_prefix( $prefix );
			}

			return true;
		}

		/**
		 * Escape `LIKE` wildcard characters, mirroring `$wpdb->esc_like()`.
		 *
		 * @param string $text Raw text.
		 * @return string
		 */
		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}
	}
}

if ( ! isset( $GLOBALS['wpdb'] ) ) {
	$GLOBALS['wpdb'] = new wpdb(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test-only stand-in for the real WordPress $wpdb global, guarded so a real WordPress environment always takes precedence.
}
