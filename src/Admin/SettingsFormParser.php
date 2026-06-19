<?php
/**
 * Parses the settings form submission into a Settings value object.
 *
 * @package CannyForge\Archive
 */

declare(strict_types=1);

namespace CannyForge\Archive\Admin;

use CannyForge\Archive\Contracts\Settings\Settings;

/**
 * Maps a raw settings-form submission to a {@see Settings} value object.
 *
 * Does the form-shape translation (checkboxes present/absent, textarea to URL
 * list); the value object itself owns range clamping and coercion, so this
 * class stays a thin, side-effect-free mapper.
 */
final class SettingsFormParser {
	/**
	 * Build a Settings value object from a raw form payload.
	 *
	 * @param array<string, mixed> $input    Raw, already-unslashed form data.
	 * @param string[]             $csv_urls URLs extracted from an uploaded CSV
	 *                                       (empty when none). Merged with — or, when
	 *                                       the "replace" box is ticked, substituted
	 *                                       for — the textarea list.
	 * @return Settings
	 */
	public function parse( array $input, array $csv_urls = array() ): Settings {
		return Settings::from_array(
			array(
				'mode'              => $this->string( $input, 'mode' ),
				'pagination_limit'  => $this->string( $input, 'pagination_limit' ),
				'archive_url'       => $this->string( $input, 'archive_url' ),
				'news_window_hours' => $this->string( $input, 'news_window_hours' ),
				'blog_max_urls'     => $this->string( $input, 'blog_max_urls' ),
				'link_types'        => array(
					'title'          => $this->checkbox( $input, 'link_title' ),
					'description'    => $this->checkbox( $input, 'link_description' ),
					'featured_image' => $this->checkbox( $input, 'link_featured_image' ),
				),
				'filters'           => array(
					'search'     => $this->checkbox( $input, 'filter_search' ),
					'category'   => $this->checkbox( $input, 'filter_category' ),
					'tag'        => $this->checkbox( $input, 'filter_tag' ),
					'month_year' => $this->checkbox( $input, 'filter_month_year' ),
					'author'     => $this->checkbox( $input, 'filter_author' ),
				),
				'blog_urls'         => $this->blog_urls( $input, $csv_urls ),
				'targeting'         => array(
					'category' => $this->checkbox( $input, 'target_category' ),
					'tag'      => $this->checkbox( $input, 'target_tag' ),
					'author'   => $this->checkbox( $input, 'target_author' ),
					'date'     => $this->checkbox( $input, 'target_date' ),
				),
				'seo'               => array(
					'title'            => $this->string( $input, 'seo_title' ),
					'meta_description' => $this->string( $input, 'seo_meta_description' ),
					'index'            => $this->checkbox( $input, 'seo_index' ),
					'follow'           => $this->checkbox( $input, 'seo_follow' ),
					'canonical'        => $this->string( $input, 'seo_canonical' ),
				),
				'theme'             => array(
					'layout'        => $this->string( $input, 'theme_layout' ),
					'accent_color'  => $this->string( $input, 'theme_accent_color' ),
					'surface_color' => $this->string( $input, 'theme_surface_color' ),
					'text_color'    => $this->string( $input, 'theme_text_color' ),
					'border_color'  => $this->string( $input, 'theme_border_color' ),
				),
				'content_selection' => array(
					'include_categories' => $this->lines( $input, 'select_include_categories' ),
					'include_tags'       => $this->lines( $input, 'select_include_tags' ),
					'exclude_categories' => $this->lines( $input, 'select_exclude_categories' ),
					'exclude_tags'       => $this->lines( $input, 'select_exclude_tags' ),
					'exclude_noindex'    => $this->checkbox( $input, 'select_exclude_noindex' ),
					'pinned_urls'        => $this->lines( $input, 'select_pinned_urls' ),
				),
			)
		);
	}

	/**
	 * Read a scalar field as a trimmed string.
	 *
	 * @param array<string, mixed> $input Raw form data.
	 * @param string               $key   Field name.
	 * @return string
	 */
	private function string( array $input, string $key ): string {
		$value = $input[ $key ] ?? '';

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * A checkbox is true when present in the payload (unchecked boxes are absent).
	 *
	 * @param array<string, mixed> $input Raw form data.
	 * @param string               $key   Field name.
	 * @return bool
	 */
	private function checkbox( array $input, string $key ): bool {
		return ! empty( $input[ $key ] );
	}

	/**
	 * Resolve the Blog URL list from the textarea plus any CSV-imported URLs.
	 *
	 * The textarea is always read. CSV URLs are merged in (appended, de-duplicated
	 * downstream by the settings model) — or, when the "replace" box is ticked and
	 * the CSV actually had URLs, they replace the textarea list entirely.
	 *
	 * @param array<string, mixed> $input    Raw form data.
	 * @param string[]             $csv_urls URLs extracted from the uploaded CSV.
	 * @return string[]
	 */
	private function blog_urls( array $input, array $csv_urls ): array {
		$typed = $this->lines( $input, 'blog_urls' );

		if ( array() === $csv_urls ) {
			return $typed;
		}

		if ( $this->checkbox( $input, 'blog_urls_csv_replace' ) ) {
			return $csv_urls;
		}

		return array_merge( $typed, $csv_urls );
	}

	/**
	 * Split a textarea field into a list of non-empty lines.
	 *
	 * @param array<string, mixed> $input Raw form data.
	 * @param string               $key   Field name.
	 * @return string[]
	 */
	private function lines( array $input, string $key ): array {
		$raw = $this->string( $input, $key );
		if ( '' === $raw ) {
			return array();
		}

		$lines = preg_split( '/[\r\n,]+/', $raw );

		return false === $lines ? array() : array_values( array_filter( array_map( 'trim', $lines ) ) );
	}
}
