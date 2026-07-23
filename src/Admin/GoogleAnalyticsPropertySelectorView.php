<?php
/**
 * Renders the Google Analytics property selector.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleSettings;

/** Presentation-only GA4 property field. */
final class GoogleAnalyticsPropertySelectorView {
	/**
	 * Render the selector and refresh action.
	 *
	 * @param GoogleSettings                                                                     $settings             Current settings.
	 * @param array<int, array{property_id: string, display_name: string, account_name: string}> $properties           Cached properties.
	 * @param string                                                                             $property_refresh_url Property refresh URL.
	 * @param bool                                                                               $analytics_ready     Whether the current grant includes Analytics access.
	 * @return void
	 */
	public function render( GoogleSettings $settings, array $properties, string $property_refresh_url, bool $analytics_ready = true ): void {
		echo '<p><label>' . esc_html__( 'GA4 Property ID', 'cannyforge-archive' ) . '<br><select';
		if ( $analytics_ready ) {
			echo ' name="google_ga4_property_id"';
		} else {
			echo ' disabled';
		}
		echo ' style="width:100%">';
		$this->render_options( $settings, $properties );
		echo '</select></label></p>';
		$this->render_actions( $analytics_ready, $property_refresh_url );
		echo '<p class="description">' . esc_html__( 'Properties load from the connected Google account. The saved numeric ID is kept selectable if access changes.', 'cannyforge-archive' ) . '</p>';
	}

	/**
	 * Render the blank and account-backed property options.
	 *
	 * @param GoogleSettings                                                                     $settings   Current settings.
	 * @param array<int, array{property_id: string, display_name: string, account_name: string}> $properties Cached properties.
	 * @return void
	 */
	private function render_options( GoogleSettings $settings, array $properties ): void {
		echo '<option value="">' . esc_html__( 'Select an Analytics property', 'cannyforge-archive' ) . '</option>';
		$known = array();
		foreach ( $properties as $property ) {
			if ( ! is_array( $property ) || ! is_string( $property['property_id'] ?? null ) ) {
				continue;
			}
			$id           = trim( $property['property_id'] );
			$known[ $id ] = true;
			$this->render_option( $settings, $property, $id );
		}
		if ( '' !== $settings->ga4_property_id() && ! isset( $known[ $settings->ga4_property_id() ] ) ) {
			printf( '<option value="%s" selected>%s</option>', esc_attr( $settings->ga4_property_id() ), esc_html( $settings->ga4_property_id() . ' (saved)' ) );
		}
	}

	/**
	 * Render one normalised property option.
	 *
	 * @param GoogleSettings       $settings Current settings.
	 * @param array<string, mixed> $property Property row.
	 * @param string               $id       Numeric property ID.
	 * @return void
	 */
	private function render_option( GoogleSettings $settings, array $property, string $id ): void {
		$display_name = is_string( $property['display_name'] ?? null ) ? trim( $property['display_name'] ) : '';
		$account_name = is_string( $property['account_name'] ?? null ) ? trim( $property['account_name'] ) : '';
		$label        = '' !== $display_name ? $display_name . ' (' . $id . ')' : $id;
		if ( '' !== $account_name ) {
			$label .= ' — ' . $account_name;
		}
		printf( '<option value="%s"%s>%s</option>', esc_attr( $id ), selected( $settings->ga4_property_id(), $id, false ), esc_html( $label ) );
	}

	/**
	 * Render the Load action or the reconnect gate.
	 *
	 * @param bool   $analytics_ready     Whether the current grant has Analytics access.
	 * @param string $property_refresh_url Property refresh URL.
	 * @return void
	 */
	private function render_actions( bool $analytics_ready, string $property_refresh_url ): void {
		if ( ! $analytics_ready ) {
			echo '<p class="description">' . esc_html__( 'Reconnect Google with Analytics access before loading or saving a GA4 property.', 'cannyforge-archive' ) . '</p>';
		} elseif ( '' !== $property_refresh_url ) {
			echo '<p class="cannyforge-google-wizard__inline-action">';
			printf(
				'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
				esc_url( $property_refresh_url ),
				esc_html__( 'Load GA4 properties', 'cannyforge-archive' )
			);
			echo '</p>';
		}
	}
}
