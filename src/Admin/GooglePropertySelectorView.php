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
		echo '<p><label>' . esc_html__( 'Search Console property', 'cannyforge-archive' ) . '<br><select name="google_search_console_site_url" style="width:100%">';
		echo '<option value="">' . esc_html__( 'Select a property after connecting Google', 'cannyforge-archive' ) . '</option>';
		$known = array();
		foreach ( $properties as $property ) {
			if ( ! is_array( $property ) || ! is_string( $property['site_url'] ?? null ) ) {
				continue;
			}
			$site_url           = trim( $property['site_url'] );
			$known[ $site_url ] = true;
			$permission         = is_string( $property['permission'] ?? null ) ? trim( $property['permission'] ) : '';
			$label              = '' !== $permission ? $site_url . ' (' . $permission . ')' : $site_url;
			printf( '<option value="%s"%s>%s</option>', esc_attr( $site_url ), selected( $settings->search_console_site_url(), $site_url, false ), esc_html( $label ) );
		}
		if ( '' !== $settings->search_console_site_url() && ! isset( $known[ $settings->search_console_site_url() ] ) ) {
			printf( '<option value="%s" selected>%s</option>', esc_attr( $settings->search_console_site_url() ), esc_html( $settings->search_console_site_url() . ' (saved)' ) );
		}
		echo '</select></label></p>';
		echo '<p class="cannyforge-google-wizard__inline-action">';
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
}
