<?php
/**
 * Renders the Google Search Console property selector.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CannyForge\Archive\Integration\Google\GoogleSettings;

/**
 * Presentation-only Search Console property field.
 */
final class GooglePropertySelectorView {
	/**
	 * Render the property selector and refresh guidance.
	 *
	 * @param GoogleSettings                                          $settings             Current Google settings.
	 * @param array<int, array{site_url: string, permission: string}> $properties           Cached properties.
	 * @param string                                                  $property_refresh_url Property refresh action URL.
	 * @return void
	 */
	public function render( GoogleSettings $settings, array $properties, string $property_refresh_url ): void {
		$property_input_id = 'cannyforge-search-console-property';
		$current_property  = $settings->search_console_site_url();
		$properties        = $this->clean_properties( $properties );

		$this->render_input( $property_input_id, $current_property );
		$this->render_datalist( $property_input_id, $current_property, $properties );
		$this->render_property_list( $properties );
		$this->render_actions( $property_refresh_url );
	}

	/**
	 * Clean property data before rendering it.
	 *
	 * @param array<int, mixed> $properties Raw properties.
	 * @return array<int, array{site_url: string, permission: string}>
	 */
	private function clean_properties( array $properties ): array {
		$clean = array();
		foreach ( $properties as $property ) {
			if ( ! is_array( $property ) || ! is_string( $property['site_url'] ?? null ) || '' === trim( $property['site_url'] ) ) {
				continue;
			}
			$clean[] = array(
				'site_url'   => trim( $property['site_url'] ),
				'permission' => is_string( $property['permission'] ?? null ) ? trim( $property['permission'] ) : '',
			);
		}
		return $clean;
	}

	/**
	 * Render the editable property input.
	 *
	 * @param string $input_id         Input ID.
	 * @param string $current_property Current property value.
	 * @return void
	 */
	private function render_input( string $input_id, string $current_property ): void {
		echo '<p><label>' . esc_html__( 'Search Console property', 'cannyforge-archive' ) . '<br>';
		printf(
			'<input type="text" id="%1$s" name="google_search_console_site_url" value="%2$s" list="%1$s-options" data-cf-google-property-input placeholder="%3$s" style="width:100%%">',
			esc_attr( $input_id ),
			esc_attr( $current_property ),
			esc_attr__( 'Choose a property below or enter one manually', 'cannyforge-archive' )
		);
		echo '</label></p>';
	}

	/**
	 * Render native datalist suggestions.
	 *
	 * @param string                                                  $input_id         Input ID.
	 * @param string                                                  $current_property Current property value.
	 * @param array<int, array{site_url: string, permission: string}> $properties       Properties.
	 * @return void
	 */
	private function render_datalist( string $input_id, string $current_property, array $properties ): void {
		echo '<datalist id="' . esc_attr( $input_id . '-options' ) . '">';
		$known = array();
		foreach ( $properties as $property ) {
			$known[ $property['site_url'] ] = true;
			printf( '<option value="%s" label="%s"></option>', esc_attr( $property['site_url'] ), esc_attr( $this->property_label( $property ) ) );
		}
		if ( '' !== $current_property && ! isset( $known[ $current_property ] ) ) {
			printf( '<option value="%s" label="%s"></option>', esc_attr( $current_property ), esc_attr( $current_property . ' (saved)' ) );
		}
		echo '</datalist>';
	}

	/**
	 * Render visible, clickable properties returned by Google.
	 *
	 * @param array<int, array{site_url: string, permission: string}> $properties Properties.
	 * @return void
	 */
	private function render_property_list( array $properties ): void {
		if ( array() === $properties ) {
			return;
		}

		echo '<div class="cannyforge-google-wizard__property-list">';
		echo '<strong>' . esc_html__( 'Properties returned by Google', 'cannyforge-archive' ) . '</strong>';
		echo '<p class="description">' . esc_html__( 'Click a property to place it in the editable field above. You can still change the value manually.', 'cannyforge-archive' ) . '</p>';
		echo '<ul>';
		foreach ( $properties as $property ) {
			printf(
				'<li><button type="button" class="button button-secondary" data-cf-google-property-option data-property-value="%s">%s</button></li>',
				esc_attr( $property['site_url'] ),
				esc_html( $this->property_label( $property ) )
			);
		}
		echo '</ul></div>';
	}

	/**
	 * Render save and refresh actions.
	 *
	 * @param string $property_refresh_url Property refresh URL.
	 * @return void
	 */
	private function render_actions( string $property_refresh_url ): void {
		echo '<p class="cannyforge-google-wizard__inline-action">';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Save property and continue', 'cannyforge-archive' ) . '</button>';
		if ( '' !== $property_refresh_url ) {
			printf(
				'<button type="submit" class="button button-secondary" formaction="%s" formmethod="post">%s</button>',
				esc_url( $property_refresh_url ),
				esc_html__( 'Load properties', 'cannyforge-archive' )
			);
		}
		echo '</p>';
		if ( '' !== $property_refresh_url ) {
			echo '<p class="description">' . esc_html__( 'Connect Google first, then load the properties available to that account and choose one here.', 'cannyforge-archive' ) . '</p>';
		}
	}

	/**
	 * Build a property label.
	 *
	 * @param array{site_url: string, permission: string} $property Property.
	 * @return string
	 */
	private function property_label( array $property ): string {
		return '' !== $property['permission'] ? $property['site_url'] . ' (' . $property['permission'] . ')' : $property['site_url'];
	}
}
